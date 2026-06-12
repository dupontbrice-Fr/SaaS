<?php
class LicenseController {
    public static function generateCode(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
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
            "SELECT l.*, s.name as screen_name, s.status as screen_status, c.name as catalog_name
             FROM licenses l
             LEFT JOIN screens s ON s.license_id = l.id AND s.archived_at IS NULL
             LEFT JOIN catalogs c ON l.catalog_id = c.id
             WHERE l.org_id = ? AND l.archived_at IS NULL
             ORDER BY l.created_at DESC",
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

    public function get(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);

        $license = Database::fetchOne(
            "SELECT l.*, c.name as catalog_name
             FROM licenses l
             LEFT JOIN catalogs c ON l.catalog_id = c.id
             WHERE l.id = ? AND l.org_id = ? AND l.archived_at IS NULL",
            [$id, $orgId]
        );
        if (!$license) { echo json_encode(['error' => 'Not found']); return; }

        $license['screens'] = Database::fetchAll(
            "SELECT id, name, status FROM screens WHERE license_id = ? AND archived_at IS NULL ORDER BY name",
            [$id]
        );

        $license['all_screens'] = Database::fetchAll(
            "SELECT s.id, s.name, s.status, s.license_id, l.code as license_code
             FROM screens s
             LEFT JOIN licenses l ON s.license_id = l.id AND l.archived_at IS NULL
             WHERE s.org_id = ? AND s.archived_at IS NULL
             ORDER BY s.name",
            [$orgId]
        );

        $license['catalogs'] = Database::fetchAll(
            "SELECT id, name FROM catalogs WHERE org_id = ? ORDER BY name",
            [$orgId]
        );

        echo json_encode($license);
    }

    public function update(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);

        $license = Database::fetchOne(
            "SELECT * FROM licenses WHERE id = ? AND org_id = ? AND archived_at IS NULL",
            [$id, $orgId]
        );
        if (!$license) { echo json_encode(['error' => 'Not found']); return; }

        $paused = (int)($_POST['paused'] ?? 0);
        $catalogId = (isset($_POST['catalog_id']) && $_POST['catalog_id'] !== '') ? (int)$_POST['catalog_id'] : null;

        Database::execute(
            "UPDATE licenses SET paused = ?, catalog_id = ? WHERE id = ? AND org_id = ?",
            [$paused, $catalogId, $id, $orgId]
        );

        echo json_encode(['success' => true]);
    }

    public function addScreen(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screenId = (int)($_POST['screen_id'] ?? 0);
        $force = ($_POST['force'] ?? '') === '1';

        $license = Database::fetchOne(
            "SELECT * FROM licenses WHERE id = ? AND org_id = ? AND archived_at IS NULL",
            [$id, $orgId]
        );
        if (!$license) { echo json_encode(['error' => 'Licence introuvable']); return; }

        $screen = Database::fetchOne(
            "SELECT s.*, l.code as current_license_code
             FROM screens s
             LEFT JOIN licenses l ON s.license_id = l.id
             WHERE s.id = ? AND s.org_id = ? AND s.archived_at IS NULL",
            [$screenId, $orgId]
        );
        if (!$screen) { echo json_encode(['error' => 'Écran introuvable']); return; }

        if ($screen['license_id'] && $screen['license_id'] != $id && !$force) {
            echo json_encode([
                'needs_confirm' => true,
                'current_license_code' => $screen['current_license_code'],
                'screen_name' => $screen['name'],
            ]);
            return;
        }

        Database::execute(
            "UPDATE screens SET license_id = ? WHERE id = ? AND org_id = ?",
            [$id, $screenId, $orgId]
        );
        echo json_encode(['success' => true]);
    }

    public function removeScreen(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $screenId = (int)($_POST['screen_id'] ?? 0);

        $screen = Database::fetchOne(
            "SELECT * FROM screens WHERE id = ? AND license_id = ? AND org_id = ?",
            [$screenId, $id, $orgId]
        );
        if (!$screen) { echo json_encode(['error' => 'Introuvable']); return; }

        Database::execute(
            "UPDATE screens SET license_id = NULL, status = 'offline' WHERE id = ? AND org_id = ?",
            [$screenId, $orgId]
        );
        echo json_encode(['success' => true]);
    }

    public function revoke(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        Database::execute("UPDATE licenses SET active = 0 WHERE id = ? AND org_id = ?", [$id, $orgId]);
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
        // Soft-delete : archiver écran et licence (conservation 30 jours)
        Database::execute(
            "UPDATE screens SET license_id = NULL, status = 'offline', archived_at = NOW() WHERE license_id = ?",
            [$id]
        );
        Database::execute(
            "UPDATE licenses SET archived_at = NOW() WHERE id = ? AND org_id = ?",
            [$id, $orgId]
        );
        echo json_encode(['success' => true]);
    }

    public function exportCsv(array $params = []): void {
        Auth::require();
        if (!Auth::isAdmin()) { http_response_code(403); exit; }
        $orgId = Auth::orgId();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ecrans-licences-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache');

        $rows = Database::fetchAll(
            "SELECT l.code AS licence,
                    CASE
                      WHEN l.archived_at IS NOT NULL THEN 'Archivée'
                      WHEN l.paused = 1 THEN 'En pause'
                      WHEN l.active = 0 THEN 'Révoquée'
                      ELSE 'Active'
                    END AS statut_licence,
                    l.created_at AS licence_creee_le,
                    l.archived_at AS licence_archivee_le,
                    c.name AS catalogue,
                    s.name AS ecran_nom,
                    s.status AS ecran_statut,
                    s.resolution, s.orientation, s.os, s.software_version,
                    s.manufacturer, s.model_number, s.serial_number,
                    s.last_seen, s.last_launch,
                    s.archived_at AS ecran_archive_le
             FROM licenses l
             LEFT JOIN screens s ON s.license_id = l.id
             LEFT JOIN catalogs c ON l.catalog_id = c.id
             WHERE l.org_id = ?
             ORDER BY l.created_at DESC",
            [$orgId]
        );

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel
        fputcsv($out, [
            'Licence', 'Statut licence', 'Créée le', 'Archivée le', 'Catalogue',
            'Écran', 'Statut écran', 'Résolution', 'Orientation', 'OS',
            'Version logiciel', 'Fabricant', 'Modèle', 'N° Série',
            'Dernier en ligne', 'Dernier lancement', 'Écran archivé le',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['licence'],
                $row['statut_licence'],
                $row['licence_creee_le'],
                $row['licence_archivee_le'] ?? '',
                $row['catalogue'] ?? '',
                $row['ecran_nom'] ?? '',
                $row['ecran_statut'] ?? '',
                $row['resolution'] ?? '',
                $row['orientation'] ?? '',
                $row['os'] ?? '',
                $row['software_version'] ?? '',
                $row['manufacturer'] ?? '',
                $row['model_number'] ?? '',
                $row['serial_number'] ?? '',
                $row['last_seen'] ?? '',
                $row['last_launch'] ?? '',
                $row['ecran_archive_le'] ?? '',
            ], ';');
        }
        fclose($out);
    }
}
