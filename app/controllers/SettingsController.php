<?php
class SettingsController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);
        if (!$settings) {
            Database::execute("INSERT INTO settings (org_id) VALUES (?)", [$orgId]);
            $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);
        }
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/settings/index.php';
    }

    public function save(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();

        $filterColor = $_POST['filter_color'] ?? '#6b6ef9';
        $delay = (int)($_POST['screensaver_delay'] ?? 300);
        $message = trim($_POST['custom_message'] ?? '');
        $widgetQr = isset($_POST['widget_qr']) ? 1 : 0;
        $widgetPmr = isset($_POST['widget_pmr']) ? 1 : 0;
        $widgetWeather = isset($_POST['widget_weather']) ? 1 : 0;
        $widgetDatetime = isset($_POST['widget_datetime']) ? 1 : 0;

        if ($delay < 30) $delay = 30;

        $logoPath = null;
        $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);
        $logoPath = $settings['logo'] ?? null;

        if (!empty($_FILES['logo']['name'])) {
            $up = Upload::handle($_FILES['logo'], 'logos', 'image');
            if ($up['success']) {
                if ($logoPath) Upload::delete($logoPath);
                $logoPath = $up['path'];
            }
        }

        Database::execute(
            "UPDATE settings SET logo=?, filter_color=?, screensaver_delay=?, custom_message=?, widget_qr=?, widget_pmr=?, widget_weather=?, widget_datetime=? WHERE org_id=?",
            [$logoPath, $filterColor, $delay, $message, $widgetQr, $widgetPmr, $widgetWeather, $widgetDatetime, $orgId]
        );

        // Update session settings
        $_SESSION['settings'] = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$orgId]);

        echo json_encode(['success' => true]);
    }
}
