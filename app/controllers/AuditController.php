<?php
class AuditController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();

        $logs = Database::fetchAll(
            "SELECT * FROM audit_logs WHERE org_id = ? ORDER BY created_at DESC LIMIT 200",
            [$orgId]
        );

        $logins = Database::fetchAll(
            "SELECT * FROM login_history WHERE org_id = ? ORDER BY logged_at DESC LIMIT 10",
            [$orgId]
        );

        $total = Database::fetchOne("SELECT COUNT(*) as c FROM audit_logs WHERE org_id = ?", [$orgId]);

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/audit/index.php';
    }
}
