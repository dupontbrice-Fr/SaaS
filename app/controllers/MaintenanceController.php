<?php
class MaintenanceController {

    public function purgeArchives(array $params = []): void {
        header('Content-Type: application/json');

        $key = $_GET['key'] ?? $_SERVER['HTTP_X_MAINTENANCE_KEY'] ?? '';
        if (!hash_equals(MAINTENANCE_KEY, $key)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));

        $screens = Database::execute(
            "DELETE FROM screens WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        $licenses = Database::execute(
            "DELETE FROM licenses WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        Audit::log('maintenance', 'purge_archives', "Purge archives < 30j : {$screens} écran(s), {$licenses} licence(s) supprimé(s)");

        echo json_encode([
            'success'          => true,
            'cutoff'           => $cutoff,
            'screens_deleted'  => $screens,
            'licenses_deleted' => $licenses,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);
    }
}
