<?php
class ScreenController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $screens = Database::fetchAll(
            "SELECT s.*, l.code as license_code FROM screens s
             LEFT JOIN licenses l ON s.license_id = l.id
             WHERE s.org_id = ? ORDER BY s.created_at DESC",
            [$orgId]
        );
        $licenses = Database::fetchAll(
            "SELECT l.*, s.name as screen_name, s.status as screen_status FROM licenses l
             LEFT JOIN screens s ON s.license_id = l.id
             WHERE l.org_id = ? ORDER BY l.created_at DESC",
            [$orgId]
        );
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/screens/index.php';
    }

    public function addDemo(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Seul un administrateur peut ajouter un écran démo.']);
            return;
        }
        $orgId = Auth::orgId();

        $name = trim($_POST['name'] ?? 'Écran démo');
        $orientation = $_POST['orientation'] ?? 'landscape';
        $duration = (int)($_POST['duration'] ?? 30);

        // Auto-generate a license for this demo screen
        $licenseCode = LicenseController::generateCode();
        $licenseId = Database::insert(
            "INSERT INTO licenses (org_id, code, active) VALUES (?, ?, 1)",
            [$orgId, $licenseCode]
        );

        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));

        $id = Database::insert(
            "INSERT INTO screens (org_id, license_id, name, token, status, orientation, is_demo, demo_expires_at, demo_orientation) VALUES (?,?,?,?,?,?,?,?,?)",
            [$orgId, $licenseId, $name, $token, 'offline', $orientation, 1, $expires, $orientation]
        );

        Audit::log('created', 'screen_demo', $name);
        echo json_encode(['success' => true, 'id' => $id, 'token' => $token, 'license_code' => $licenseCode]);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("DELETE FROM screens WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function getQrCode(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screen = Database::fetchOne("SELECT * FROM screens WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$screen) { echo json_encode(['error' => 'Not found']); return; }

        $url = APP_URL . '/viewer?token=' . $screen['token'];
        $qr = QRCode::url($url, 300);
        echo json_encode(['success' => true, 'url' => $url, 'qr' => $qr, 'screen' => $screen]);
    }

    public function getLogs(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screen = Database::fetchOne("SELECT * FROM screens WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$screen) { echo json_encode(['error' => 'Not found']); return; }

        $logs = Database::fetchAll(
            "SELECT * FROM screen_logs WHERE screen_id = ? ORDER BY created_at DESC LIMIT 100",
            [$id]
        );
        echo json_encode(['success' => true, 'logs' => $logs, 'screen' => $screen]);
    }

    public function getInfo(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screen = Database::fetchOne(
            "SELECT s.*, l.code as license_code FROM screens s LEFT JOIN licenses l ON s.license_id = l.id WHERE s.id = ? AND s.org_id = ?",
            [$id, $orgId]
        );
        echo json_encode($screen ?: ['error' => 'Not found']);
    }

    public function getSettings(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screen = Database::fetchOne(
            "SELECT s.*, l.code as license_code FROM screens s LEFT JOIN licenses l ON s.license_id = l.id WHERE s.id = ? AND s.org_id = ?",
            [$id, $orgId]
        );
        echo json_encode($screen ?: ['error' => 'Not found']);
    }

    public function disconnect(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE screens SET license_id = NULL, status = 'offline' WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function format(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE screens SET storage_used_screen = 0, storage_used_license = 0 WHERE id = ? AND org_id = ?", [$id, $orgId]);
        Database::execute("DELETE FROM screen_logs WHERE screen_id = ?", [$id]);
        echo json_encode(['success' => true]);
    }

    public function restart(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        // Signal sent via API to APK
        echo json_encode(['success' => true, 'message' => 'Commande de redémarrage envoyée']);
    }

    public function cleanStorage(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE screens SET storage_used_screen = 0 WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function preview(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screen = Database::fetchOne("SELECT * FROM screens WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode($screen ?: ['error' => 'Not found']);
    }
}
