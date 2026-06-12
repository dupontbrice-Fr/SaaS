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
        $totals = [];

        // ── Screens ──────────────────────────────────────────────
        $totals['screens'] = Database::execute(
            "DELETE FROM screens WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Licenses ─────────────────────────────────────────────
        $totals['licenses'] = Database::execute(
            "DELETE FROM licenses WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Products (+ physical files) ───────────────────────────
        $expiredProducts = Database::fetchAll(
            "SELECT file_path, thumbnail FROM products WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        foreach ($expiredProducts as $p) {
            if ($p['file_path']) Upload::delete($p['file_path']);
            if ($p['thumbnail']) Upload::delete($p['thumbnail']);
        }
        $totals['products'] = Database::execute(
            "DELETE FROM products WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Categories (+ physical images) ───────────────────────
        $expiredCats = Database::fetchAll(
            "SELECT image FROM categories WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        foreach ($expiredCats as $c) {
            if ($c['image']) Upload::delete($c['image']);
        }
        $totals['categories'] = Database::execute(
            "DELETE FROM categories WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Banners (+ physical files) ────────────────────────────
        $expiredBanners = Database::fetchAll(
            "SELECT file_path FROM banners WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        foreach ($expiredBanners as $b) {
            if ($b['file_path']) Upload::delete($b['file_path']);
        }
        $totals['banners'] = Database::execute(
            "DELETE FROM banners WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Screensavers (+ physical files) ──────────────────────
        $expiredSs = Database::fetchAll(
            "SELECT file_path FROM screensavers WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        foreach ($expiredSs as $s) {
            if ($s['file_path']) Upload::delete($s['file_path']);
        }
        $totals['screensavers'] = Database::execute(
            "DELETE FROM screensavers WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        // ── Media library (+ physical files) ─────────────────────
        $expiredMedia = Database::fetchAll(
            "SELECT path FROM media_library WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );
        foreach ($expiredMedia as $m) {
            if ($m['path']) Upload::delete($m['path']);
        }
        $totals['media_library'] = Database::execute(
            "DELETE FROM media_library WHERE archived_at IS NOT NULL AND archived_at < ?",
            [$cutoff]
        );

        $summary = implode(', ', array_map(
            fn($t, $n) => "{$n} {$t}",
            array_keys($totals),
            array_values($totals)
        ));
        Audit::log('maintenance', 'purge_archives', "Purge archives < 30j : {$summary}");

        echo json_encode([
            'success'  => true,
            'cutoff'   => $cutoff,
            'deleted'  => $totals,
            'run_at'   => date('Y-m-d H:i:s'),
        ]);
    }
}
