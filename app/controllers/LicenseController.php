<?php
class LicenseController {
    /**
     * Generate a unique 8-character device code (like LTOKJGOQ)
     */
    public static function generateCode(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No confusing chars
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (Database::fetchOne("SELECT id FROM licenses WHERE code = ?", [$code]));
        return $code;
    }

    public function index(array $params = []): void {
        Auth::require();
        if (!Auth::isAdmin()) { header('Location: /manage/catalog'); exit; }
        $orgId = Auth::orgId();
        $licenses = Database::fetchAll(
            "SELECT l.*, s.name as screen_name, s.status as screen_status FROM licenses l LEFT JOIN screens s ON s.license_id = l.id WHERE l.org_id = ? ORDER BY l.created_at DESC",
            [$orgId]
        );
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/licenses/index.php';
    }

    public function create(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $count = max(1, min(10, (int)($_POST['count'] ?? 1)));

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = self::generateCode();
            $id = Database::insert(
                "INSERT INTO licenses (org_id, code, active) VALUES (?, ?, 1)",
                [$orgId, $code]
            );
            $codes[] = ['id' => $id, 'code' => $code];
        }

        Audit::log('created', 'license', $count . ' licence(s) générée(s)');
        echo json_encode(['success' => true, 'licenses' => $codes]);
    }

    public function revoke(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE licenses SET active = 0 WHERE id = ? AND org_id = ?", [$id, $orgId]);
        // Disconnect related screen
        Database::execute("UPDATE screens SET status = 'offline' WHERE license_id = ?", [$id]);
        echo json_encode(['success' => true]);
    }

    public function activate(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE licenses SET active = 1 WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        // Disconnect screen
        Database::execute("UPDATE screens SET license_id = NULL, status = 'offline' WHERE license_id = ?", [$id]);
        Database::execute("DELETE FROM licenses WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }
}
