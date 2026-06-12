<?php
/**
 * MediaPush REST API v1
 * Used by the Android APK to connect screens
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-License-Code');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api/v1#', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Helper: get JSON body
 */
function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

/**
 * Helper: response
 */
function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

/**
 * Validate license code from header or body
 */
function getLicenseCode(): ?string {
    return $_SERVER['HTTP_X_LICENSE_CODE'] ?? jsonBody()['license_code'] ?? null;
}

/**
 * Authenticate screen via license code
 */
function authenticateScreen(string $code): ?array {
    return Database::fetchOne(
        "SELECT l.*, s.*, l.code as license_code FROM licenses l LEFT JOIN screens s ON s.license_id = l.id WHERE l.code = ? AND l.active = 1 AND l.paused = 0 AND l.archived_at IS NULL",
        [$code]
    );
}

// ── Routes ──────────────────────────────────────────────────────

// POST /api/v1/screen/register - Register screen with license code
if ($method === 'POST' && $uri === '/screen/register') {
    $body = jsonBody();
    $code = $body['license_code'] ?? '';

    if (empty($code)) respond(['error' => 'license_code required'], 400);

    $license = Database::fetchOne("SELECT * FROM licenses WHERE code = ? AND active = 1 AND paused = 0 AND archived_at IS NULL", [$code]);
    if (!$license) respond(['error' => 'Invalid or inactive license'], 401);

    // Update or create screen
    $screen = Database::fetchOne("SELECT * FROM screens WHERE license_id = ? AND archived_at IS NULL", [$license['id']]);

    $updateData = [
        'status' => 'online',
        'resolution' => $body['resolution'] ?? null,
        'orientation' => $body['orientation'] ?? 'landscape',
        'os' => $body['os'] ?? null,
        'software_version' => $body['software_version'] ?? null,
        'manufacturer' => $body['manufacturer'] ?? null,
        'model_number' => $body['model_number'] ?? null,
        'serial_number' => $body['serial_number'] ?? null,
        'android_id' => $body['android_id'] ?? null,
        'firmware' => $body['firmware'] ?? null,
        'last_seen' => date('Y-m-d H:i:s'),
        'last_launch' => date('Y-m-d H:i:s'),
    ];

    if ($screen) {
        // Update existing screen
        Database::execute(
            "UPDATE screens SET status=?, resolution=?, orientation=?, os=?, software_version=?, manufacturer=?, model_number=?, serial_number=?, android_id=?, firmware=?, last_seen=?, last_launch=? WHERE id=?",
            array_merge(array_values($updateData), [$screen['id']])
        );
        $token = $screen['token'];
        $screenId = $screen['id'];
        $orgId = $screen['org_id'];
    } else {
        // Create screen linked to license
        $token = bin2hex(random_bytes(16));
        $name = $body['device_name'] ?? 'Écran ' . substr($code, 0, 4);
        $screenId = Database::insert(
            "INSERT INTO screens (org_id, license_id, name, token, status, resolution, orientation, os, software_version, manufacturer, model_number, serial_number, android_id, firmware, last_seen, last_launch) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$license['org_id'], $license['id'], $name, $token, 'online', $updateData['resolution'], $updateData['orientation'], $updateData['os'], $updateData['software_version'], $updateData['manufacturer'], $updateData['model_number'], $updateData['serial_number'], $updateData['android_id'], $updateData['firmware'], $updateData['last_seen'], $updateData['last_launch']]
        );
        $orgId = $license['org_id'];
    }

    // Log registration
    Database::execute("INSERT INTO screen_logs (screen_id, level, message) VALUES (?, 'info', ?)", [$screenId, 'Screen registered/connected at ' . date('Y-m-d H:i:s')]);

    respond([
        'success' => true,
        'token' => $token,
        'screen_id' => $screenId,
        'org_id' => $orgId,
        'viewer_url' => APP_URL . '/viewer?token=' . $token,
    ]);
}

// GET /api/v1/catalog?token=xxx - Get catalog for screen
if ($method === 'GET' && $uri === '/catalog') {
    $token = $_GET['token'] ?? '';
    $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ? AND status != 'offline' AND archived_at IS NULL", [$token]);
    if (!$screen) {
        // Try demo screen (offline or no license)
        $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ? AND archived_at IS NULL", [$token]);
        if (!$screen) respond(['error' => 'Invalid token'], 401);
    }

    $orgId = $screen['org_id'];

    // Catalog priority: license.catalog_id > screen.catalog_id (demo fallback)
    $catalogId = null;
    if ($screen['license_id']) {
        $lic = Database::fetchOne(
            "SELECT catalog_id FROM licenses WHERE id = ? AND archived_at IS NULL",
            [$screen['license_id']]
        );
        $catalogId = $lic['catalog_id'] ?? null;
    } else {
        $catalogId = $screen['catalog_id'] ?? null;
    }

    if ($catalogId) {
        // Fetch all active org categories, then include only those whose root ancestor belongs to the catalog
        $allOrgCats = Database::fetchAll(
            "SELECT * FROM categories WHERE org_id = ? AND archived_at IS NULL AND status = 'active' ORDER BY position ASC",
            [$orgId]
        );
        // BFS from catalog root categories to collect all descendants
        $included = [];
        $queue    = [];
        foreach ($allOrgCats as $c) {
            if ((int)$c['catalog_id'] === (int)$catalogId && !(int)$c['parent_id']) {
                $queue[] = (int)$c['id'];
            }
        }
        while (!empty($queue)) {
            $id = array_shift($queue);
            if (isset($included[$id])) continue;
            $included[$id] = true;
            foreach ($allOrgCats as $c) {
                if ((int)$c['parent_id'] === $id) {
                    $queue[] = (int)$c['id'];
                }
            }
        }
        $categories = array_values(array_filter($allOrgCats, fn($c) => isset($included[(int)$c['id']])));
    } else {
        $categories = Database::fetchAll(
            "SELECT * FROM categories WHERE org_id = ? AND archived_at IS NULL AND status = 'active' ORDER BY position ASC",
            [$orgId]
        );
    }

    foreach ($categories as &$cat) {
        $cat['products'] = Database::fetchAll(
            "SELECT * FROM products WHERE org_id = ? AND category_id = ? AND archived_at IS NULL AND status = 'active' ORDER BY position ASC",
            [$orgId, $cat['id']]
        );
        foreach ($cat['products'] as &$prod) {
            if ($prod['file_path']) $prod['file_url'] = UPLOAD_URL . $prod['file_path'];
            if ($prod['thumbnail']) $prod['thumbnail_url'] = UPLOAD_URL . $prod['thumbnail'];
        }
        if ($cat['image']) $cat['image_url'] = UPLOAD_URL . $cat['image'];
    }

    // Root products (no category)
    $rootProducts = Database::fetchAll(
        "SELECT * FROM products WHERE org_id = ? AND category_id IS NULL AND archived_at IS NULL AND status = 'active' ORDER BY position ASC",
        [$orgId]
    );
    foreach ($rootProducts as &$prod) {
        if ($prod['file_path']) $prod['file_url'] = UPLOAD_URL . $prod['file_path'];
        if ($prod['thumbnail']) $prod['thumbnail_url'] = UPLOAD_URL . $prod['thumbnail'];
    }

    $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);
    $banners = Database::fetchAll(
        "SELECT * FROM banners WHERE org_id = ? AND status = 'active' AND archived_at IS NULL ORDER BY position ASC",
        [$orgId]
    );
    foreach ($banners as &$b) {
        if ($b['file_path']) $b['file_url'] = UPLOAD_URL . $b['file_path'];
    }
    $screensavers = Database::fetchAll(
        "SELECT * FROM screensavers WHERE org_id = ? AND status = 'active' AND archived_at IS NULL ORDER BY position ASC",
        [$orgId]
    );
    foreach ($screensavers as &$s) {
        if ($s['file_path']) $s['file_url'] = UPLOAD_URL . $s['file_path'];
    }

    respond([
        'success' => true,
        'screen' => $screen,
        'categories' => $categories,
        'root_products' => $rootProducts,
        'banners' => $banners,
        'screensavers' => $screensavers,
        'settings' => $settings,
    ]);
}

// POST /api/v1/screen/heartbeat - APK sends periodic status
if ($method === 'POST' && $uri === '/screen/heartbeat') {
    $body = jsonBody();
    $token = $body['token'] ?? '';
    $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ? AND archived_at IS NULL", [$token]);
    if (!$screen) respond(['error' => 'Invalid token'], 401);

    Database::execute(
        "UPDATE screens SET status='online', last_seen=NOW(), storage_used_screen=?, storage_used_license=?, storage_available=? WHERE id=?",
        [
            $body['storage_used_screen'] ?? $screen['storage_used_screen'],
            $body['storage_used_license'] ?? $screen['storage_used_license'],
            $body['storage_available'] ?? $screen['storage_available'],
            $screen['id']
        ]
    );

    respond(['success' => true, 'timestamp' => time()]);
}

// POST /api/v1/screen/offline - Mark screen offline
if ($method === 'POST' && $uri === '/screen/offline') {
    $body = jsonBody();
    $token = $body['token'] ?? '';
    Database::execute("UPDATE screens SET status='offline' WHERE token=?", [$token]);
    respond(['success' => true]);
}

// POST /api/v1/screen/log - Add log entry
if ($method === 'POST' && $uri === '/screen/log') {
    $body = jsonBody();
    $token = $body['token'] ?? '';
    $screen = Database::fetchOne("SELECT id FROM screens WHERE token = ?", [$token]);
    if ($screen) {
        Database::execute(
            "INSERT INTO screen_logs (screen_id, level, message) VALUES (?, ?, ?)",
            [$screen['id'], $body['level'] ?? 'info', $body['message'] ?? '']
        );
    }
    respond(['success' => true]);
}

// POST /api/v1/track - Track a click
if ($method === 'POST' && $uri === '/track') {
    $body = jsonBody();
    $token = $body['token'] ?? '';
    $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ?", [$token]);
    if (!$screen) respond(['error' => 'Invalid token'], 401);

    Database::execute(
        "INSERT INTO statistics (org_id, screen_id, license_code, item_type, item_id, item_name, is_external) VALUES (?,?,?,?,?,?,?)",
        [$screen['org_id'], $screen['id'], $body['license_code'] ?? null, $body['item_type'] ?? 'product', (int)($body['item_id'] ?? 0), $body['item_name'] ?? '', $body['is_external'] ?? 0]
    );
    respond(['success' => true]);
}

// POST /api/v1/license/validate - Validate a license code
if ($method === 'POST' && $uri === '/license/validate') {
    $body = jsonBody();
    $code = strtoupper(trim($body['code'] ?? ''));
    $license = Database::fetchOne(
        "SELECT l.*, o.name as org_name FROM licenses l JOIN organizations o ON l.org_id = o.id WHERE l.code = ? AND l.active = 1 AND l.paused = 0 AND l.archived_at IS NULL",
        [$code]
    );
    if (!$license) respond(['valid' => false, 'error' => 'Code invalide ou inactif'], 401);
    respond(['valid' => true, 'org_name' => $license['org_name']]);
}

// 404
respond(['error' => 'Endpoint not found: ' . $uri], 404);
