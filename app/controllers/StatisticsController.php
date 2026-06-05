<?php
class StatisticsController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $period = $_GET['period'] ?? 'today';
        $screenId = $_GET['screen_id'] ?? '';
        $showExternal = isset($_GET['external']);

        [$dateFrom, $dateTo] = $this->getDateRange($period);

        $where = "WHERE s.org_id = ?";
        $queryParams = [$orgId];
        if ($screenId) { $where .= " AND s.screen_id = ?"; $queryParams[] = $screenId; }
        if (!$showExternal) { $where .= " AND s.is_external = 0"; }
        $where .= " AND s.clicked_at BETWEEN ? AND ?";
        $queryParams[] = $dateFrom;
        $queryParams[] = $dateTo;

        $clicks = Database::fetchAll(
            "SELECT s.*, sc.name as screen_name FROM statistics s LEFT JOIN screens sc ON s.screen_id = sc.id {$where} ORDER BY s.clicked_at DESC LIMIT 50",
            $queryParams
        );

        // Stats
        $totalAll = Database::fetchOne("SELECT COUNT(*) as c FROM statistics WHERE org_id = ?", [$orgId]);
        $totalMonth = Database::fetchOne("SELECT COUNT(*) as c FROM statistics WHERE org_id = ? AND MONTH(clicked_at) = MONTH(NOW()) AND YEAR(clicked_at) = YEAR(NOW())", [$orgId]);
        $totalToday = Database::fetchOne("SELECT COUNT(*) as c FROM statistics WHERE org_id = ? AND DATE(clicked_at) = CURDATE()", [$orgId]);

        // Daily chart data (last 30 days)
        $chartData = Database::fetchAll(
            "SELECT DATE(clicked_at) as day, COUNT(*) as count FROM statistics WHERE org_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(clicked_at) ORDER BY day ASC",
            [$orgId]
        );

        // Top categories
        $topCategories = Database::fetchAll(
            "SELECT item_name, COUNT(*) as count FROM statistics WHERE org_id = ? AND item_type = 'category' GROUP BY item_name ORDER BY count DESC LIMIT 5",
            [$orgId]
        );

        // Top products
        $topProducts = Database::fetchAll(
            "SELECT item_name, COUNT(*) as count FROM statistics WHERE org_id = ? AND item_type = 'product' GROUP BY item_name ORDER BY count DESC LIMIT 5",
            [$orgId]
        );

        $screens = Database::fetchAll("SELECT id, name FROM screens WHERE org_id = ?", [$orgId]);

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/statistics/index.php';
    }

    public function trackClick(array $params = []): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) { echo json_encode(['success' => false]); return; }

        // Validate token
        $token = $data['token'] ?? '';
        $screen = Database::fetchOne("SELECT * FROM screens WHERE token = ?", [$token]);
        if (!$screen) { echo json_encode(['success' => false, 'error' => 'Invalid token']); return; }

        $itemType = $data['item_type'] ?? 'product';
        $itemId = (int)($data['item_id'] ?? 0);
        $itemName = $data['item_name'] ?? '';
        $isExternal = $data['is_external'] ?? 0;

        Database::execute(
            "INSERT INTO statistics (org_id, screen_id, license_code, item_type, item_id, item_name, is_external) VALUES (?,?,?,?,?,?,?)",
            [$screen['org_id'], $screen['id'], $data['license_code'] ?? null, $itemType, $itemId, $itemName, $isExternal]
        );

        echo json_encode(['success' => true]);
    }

    public function export(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        // Export as CSV
        $data = Database::fetchAll(
            "SELECT s.item_type as TYPE, s.item_name as NOM_DU_BLOC, s.license_code as LICENSE, COUNT(*) as CLICS FROM statistics s WHERE s.org_id = ? GROUP BY s.item_type, s.item_name, s.license_code ORDER BY CLICS DESC",
            [$orgId]
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="statistiques_' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['TYPE', 'NOM DU BLOC', 'LICENSE', 'NOMBRE DE CLICS'], ';');
        foreach ($data as $row) {
            fputcsv($f, $row, ';');
        }
        fclose($f);
        exit;
    }

    private function getDateRange(string $period): array {
        switch ($period) {
            case 'today': return [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
            case 'yesterday':
                $y = date('Y-m-d', strtotime('-1 day'));
                return [$y . ' 00:00:00', $y . ' 23:59:59'];
            case 'week': return [date('Y-m-d', strtotime('monday this week')) . ' 00:00:00', date('Y-m-d 23:59:59')];
            case 'last_week':
                return [date('Y-m-d', strtotime('monday last week')) . ' 00:00:00', date('Y-m-d', strtotime('sunday last week')) . ' 23:59:59'];
            case 'month': return [date('Y-m-01 00:00:00'), date('Y-m-d 23:59:59')];
            case 'last_month':
                return [date('Y-m-01 00:00:00', strtotime('first day of last month')), date('Y-m-t 23:59:59', strtotime('last month'))];
            case 'all':
            default: return ['2000-01-01 00:00:00', date('Y-m-d 23:59:59')];
        }
    }
}
