<?php
/**
 * MultiApp - Front Controller
 */

// Bootstrap
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/helpers/upload.php';
require_once __DIR__ . '/helpers/audit.php';
require_once __DIR__ . '/helpers/qrcode.php';
require_once __DIR__ . '/helpers/pdf.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CatalogController.php';
require_once __DIR__ . '/controllers/BannerController.php';
require_once __DIR__ . '/controllers/ScreensaverController.php';
require_once __DIR__ . '/controllers/ScreenController.php';
require_once __DIR__ . '/controllers/StatisticsController.php';
require_once __DIR__ . '/controllers/CertificateController.php';
require_once __DIR__ . '/controllers/LibraryController.php';
require_once __DIR__ . '/controllers/AuditController.php';
require_once __DIR__ . '/controllers/SettingsController.php';
require_once __DIR__ . '/controllers/LicenseController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/MaintenanceController.php';

// Session
session_name(SESSION_NAME);
session_start();

// API routes (separate handler)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($uri, '/api/v1')) {
    require_once __DIR__ . '/api/v1/index.php';
    exit;
}
if ($uri === '/api/update') {
    require_once __DIR__ . '/api/update.php';
    exit;
}

$router = new Router();

// ── Auth ──────────────────────────────────────────────────────
$router->get('/login', [AuthController::class, 'loginPage']);
$router->post('/login', [AuthController::class, 'loginPost']);
$router->get('/logout', [AuthController::class, 'logout']);

// Root → redirect to catalog
$router->get('/', function() { header('Location: /manage/catalog'); exit; });

// ── Catalog ──────────────────────────────────────────────────
$router->get('/manage/catalog', [CatalogController::class, 'index']);
$router->get('/manage/catalog/categories/{id}', [CatalogController::class, 'catalogCategories']);
$router->post('/manage/catalog/catalog/create', [CatalogController::class, 'createCatalog']);
$router->get('/manage/catalog/catalog/{id}/get', [CatalogController::class, 'getCatalog']);
$router->post('/manage/catalog/catalog/{id}', [CatalogController::class, 'editCatalog']);
$router->post('/manage/catalog/catalog/{id}/delete', [CatalogController::class, 'deleteCatalog']);
$router->post('/manage/catalog/assign-screen/{screen_id}', [CatalogController::class, 'assignScreenCatalog']);
$router->get('/manage/catalog/browse/{id}', [CatalogController::class, 'browse']);

// Category
$router->post('/manage/catalog/category/add', [CatalogController::class, 'addCategory']);
$router->get('/manage/catalog/category/{id}/get', [CatalogController::class, 'getCategory']);
$router->post('/manage/catalog/category/{id}', [CatalogController::class, 'editCategory']);
$router->post('/manage/catalog/category/{id}/delete', [CatalogController::class, 'deleteCategory']);
$router->post('/manage/catalog/category/{id}/restore', [CatalogController::class, 'restoreCategory']);
$router->post('/manage/catalog/category/{id}/hard-delete', [CatalogController::class, 'hardDeleteCategory']);

// Product
$router->post('/manage/catalog/product/add', [CatalogController::class, 'addProduct']);
$router->get('/manage/catalog/product/{id}/get', [CatalogController::class, 'getProduct']);
$router->post('/manage/catalog/product/{id}', [CatalogController::class, 'editProduct']);
$router->post('/manage/catalog/product/{id}/delete', [CatalogController::class, 'deleteProduct']);
$router->post('/manage/catalog/product/{id}/restore', [CatalogController::class, 'restoreProduct']);
$router->post('/manage/catalog/product/{id}/hard-delete', [CatalogController::class, 'hardDeleteProduct']);
$router->post('/manage/catalog/product/{id}/duplicate', [CatalogController::class, 'duplicateProduct']);
// moveProduct removed — products always belong to a category
$router->post('/manage/catalog/reorder', [CatalogController::class, 'reorder']);

// ── Banners ──────────────────────────────────────────────────
$router->get('/manage/banners', [BannerController::class, 'index']);
$router->post('/manage/banners/add', [BannerController::class, 'add']);
$router->get('/manage/banners/{id}/get', [BannerController::class, 'get']);
$router->post('/manage/banners/{id}', [BannerController::class, 'edit']);
$router->post('/manage/banners/{id}/delete', [BannerController::class, 'delete']);
$router->post('/manage/banners/reorder', [BannerController::class, 'reorder']);

// ── Screensaver ──────────────────────────────────────────────
$router->get('/manage/screensaver', [ScreensaverController::class, 'index']);
$router->post('/manage/screensaver/add', [ScreensaverController::class, 'add']);
$router->get('/manage/screensaver/{id}/get', [ScreensaverController::class, 'get']);
$router->post('/manage/screensaver/{id}', [ScreensaverController::class, 'edit']);
$router->post('/manage/screensaver/{id}/delete', [ScreensaverController::class, 'delete']);

// ── Library (Bibliothèques) ───────────────────────────────────
$router->get('/manage/library', [LibraryController::class, 'index']);
$router->get('/manage/library/picker', [LibraryController::class, 'picker']);
$router->post('/manage/library/upload', [LibraryController::class, 'upload']);
$router->post('/manage/library/bulk-archive', [LibraryController::class, 'bulkArchive']);
$router->post('/manage/library/bulk-restore', [LibraryController::class, 'bulkRestore']);
$router->post('/manage/library/bulk-delete',  [LibraryController::class, 'bulkDelete']);
$router->post('/manage/library/{id}/archive', [LibraryController::class, 'archive']);
$router->post('/manage/library/{id}/restore', [LibraryController::class, 'restore']);
$router->post('/manage/library/{id}/delete',  [LibraryController::class, 'delete']);

// ── Screens ──────────────────────────────────────────────────
$router->get('/manage/screens', [ScreenController::class, 'index']);
$router->post('/manage/screens/demo/add', [ScreenController::class, 'addDemo']);
$router->post('/manage/screens/{id}/delete', [ScreenController::class, 'delete']);
$router->get('/manage/screens/{id}/qrcode', [ScreenController::class, 'getQrCode']);
$router->get('/manage/screens/{id}/logs', [ScreenController::class, 'getLogs']);
$router->get('/manage/screens/{id}/info', [ScreenController::class, 'getInfo']);
$router->get('/manage/screens/{id}/settings', [ScreenController::class, 'getSettings']);
$router->get('/manage/screens/{id}/preview', [ScreenController::class, 'preview']);
$router->post('/manage/screens/{id}/disconnect', [ScreenController::class, 'disconnect']);
$router->post('/manage/screens/{id}/format', [ScreenController::class, 'format']);
$router->post('/manage/screens/{id}/restart', [ScreenController::class, 'restart']);
$router->post('/manage/screens/{id}/clean-storage', [ScreenController::class, 'cleanStorage']);

// ── Statistics ───────────────────────────────────────────────
$router->get('/manage/statistics', [StatisticsController::class, 'index']);
$router->get('/manage/statistics/export', [StatisticsController::class, 'export']);
$router->post('/api/track', [StatisticsController::class, 'trackClick']);

// ── Certificates ─────────────────────────────────────────────
$router->get('/manage/certificates', [CertificateController::class, 'index']);
$router->post('/manage/certificates/generate/{id}', [CertificateController::class, 'generate']);
$router->get('/manage/certificates/download/{id}', [CertificateController::class, 'download']);
$router->post('/manage/certificates/{id}/delete', [CertificateController::class, 'delete']);
$router->get('/manage/certificates/zip', [CertificateController::class, 'downloadZip']);

// ── Users ────────────────────────────────────────────────────
$router->get('/manage/users', [UserController::class, 'index']);
$router->post('/manage/users/create', [UserController::class, 'create']);
$router->post('/manage/users/{id}/delete', [UserController::class, 'delete']);

// ── Licenses ─────────────────────────────────────────────────
$router->get('/manage/licenses', [LicenseController::class, 'index']);
$router->post('/manage/licenses/create', [LicenseController::class, 'create']);
$router->get('/manage/licenses/export-csv', [LicenseController::class, 'exportCsv']);
$router->get('/manage/licenses/{id}/get', [LicenseController::class, 'get']);
$router->post('/manage/licenses/{id}/update', [LicenseController::class, 'update']);
$router->post('/manage/licenses/{id}/add-screen', [LicenseController::class, 'addScreen']);
$router->post('/manage/licenses/{id}/remove-screen', [LicenseController::class, 'removeScreen']);
$router->post('/manage/licenses/{id}/revoke', [LicenseController::class, 'revoke']);
$router->post('/manage/licenses/{id}/activate', [LicenseController::class, 'activate']);
$router->post('/manage/licenses/{id}/delete', [LicenseController::class, 'delete']);

// ── Maintenance (Make.com webhook) ───────────────────────────
$router->get('/api/maintenance/purge-archives', [MaintenanceController::class, 'purgeArchives']);

// ── Audit ────────────────────────────────────────────────────
$router->get('/audit', [AuditController::class, 'index']);

// ── Settings ─────────────────────────────────────────────────
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings/save', [SettingsController::class, 'save']);

// ── Support ──────────────────────────────────────────────────
$router->get('/support', function() {
    Auth::require();
    include __DIR__ . '/views/layout.php';
    echo '<div style="text-align:center;padding:60px;color:var(--text-muted);">';
    echo '<div style="font-size:48px;margin-bottom:16px;">🎯</div>';
    echo '<h2>Support MultiApp</h2>';
    echo '<p style="margin-top:12px;">Contactez-nous : <a href="mailto:support@multiapp.fr" style="color:var(--accent);">support@multiapp.fr</a></p>';
    echo '</div>';
    include __DIR__ . '/views/layout_footer.php';
});

// ── Viewer (public screen viewer) ────────────────────────────
$router->get('/viewer', function() {
    $token = $_GET['token'] ?? '';
    $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ? AND archived_at IS NULL", [$token]);
    if (!$screen) { http_response_code(404); echo '404 - Écran introuvable'; exit; }

    // Update screen last seen
    Database::execute("UPDATE screens SET last_seen = NOW() WHERE token = ?", [$token]);

    // Get catalog data (filtered by assigned catalog if set)
    $orgId     = $screen['org_id'];
    $catalogId = $screen['catalog_id'] ?? null;
    if ($catalogId) {
        $categories = Database::fetchAll(
            "SELECT * FROM categories WHERE org_id = ? AND catalog_id = ? AND archived_at IS NULL AND status = 'active' ORDER BY position ASC",
            [$orgId, $catalogId]
        );
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
    }
    $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);

    $screensavers = Database::fetchAll(
        "SELECT * FROM screensavers WHERE org_id = ? AND status = 'active' ORDER BY position ASC",
        [$orgId]
    );
    foreach ($screensavers as &$sv) {
        if ($sv['file_path']) $sv['file_url'] = '/public/uploads/' . $sv['file_path'];
    }
    unset($sv);

    include __DIR__ . '/views/viewer.php';
});

$router->dispatch();
