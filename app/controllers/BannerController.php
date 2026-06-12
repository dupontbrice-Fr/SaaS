<?php
class BannerController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $banners = Database::fetchAll(
            "SELECT b.*, c.name as catalog_name
             FROM banners b
             LEFT JOIN catalogs c ON b.catalog_id = c.id
             WHERE b.org_id = ? AND b.archived_at IS NULL ORDER BY b.position ASC, b.created_at DESC",
            [$orgId]
        );
        $catalogs = Database::fetchAll(
            "SELECT id, name FROM catalogs WHERE org_id = ? ORDER BY name ASC",
            [$orgId]
        );
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/banners/index.php';
    }

    public function add(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();

        $name      = trim($_POST['name'] ?? '');
        $fileType  = $_POST['file_type'] ?? 'media';
        $status    = $_POST['status'] ?? 'active';
        $url       = trim($_POST['url'] ?? '');
        $catalogId = (isset($_POST['catalog_id']) && $_POST['catalog_id'] !== '') ? (int)$_POST['catalog_id'] : null;

        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Le titre est requis']);
            return;
        }

        $filePath = null;
        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'banners', 'media');
            if (!$up['success']) { echo json_encode($up); return; }
            $filePath = $up['path'];
            Database::execute(
                "INSERT INTO media_library (org_id, filename, original_name, file_type, mime_type, file_size, path, used_in) VALUES (?,?,?,?,?,?,?,?)",
                [$orgId, $up['filename'], $up['original_name'], Upload::mediaType($up['mime']), $up['mime'], $up['size'], $up['path'], 'banner']
            );
        } elseif ($fileType === 'media') {
            $libId = (int)($_POST['library_media_id'] ?? 0);
            if ($libId) {
                $lib = Database::fetchOne("SELECT * FROM media_library WHERE id = ? AND org_id = ?", [$libId, $orgId]);
                if ($lib) $filePath = $lib['path'];
            }
        }

        $maxPos = Database::fetchOne("SELECT MAX(position) as m FROM banners WHERE org_id = ?", [$orgId]);
        $id = Database::insert(
            "INSERT INTO banners (org_id, catalog_id, name, file_type, file_path, url, status, position) VALUES (?,?,?,?,?,?,?,?)",
            [$orgId, $catalogId, $name, $fileType, $filePath, $url, $status, ($maxPos['m'] ?? 0) + 1]
        );

        Audit::log('created', 'banner', $name);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function edit(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);

        $banner = Database::fetchOne("SELECT * FROM banners WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$banner) { echo json_encode(['success' => false]); return; }

        $name      = trim($_POST['name'] ?? $banner['name']);
        $fileType  = $_POST['file_type'] ?? $banner['file_type'];
        $status    = $_POST['status'] ?? $banner['status'];
        $url       = trim($_POST['url'] ?? $banner['url'] ?? '');
        $catalogId = (isset($_POST['catalog_id']) && $_POST['catalog_id'] !== '') ? (int)$_POST['catalog_id'] : null;
        $filePath  = $banner['file_path'];

        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'banners', 'media');
            if ($up['success']) {
                if ($filePath) Upload::delete($filePath);
                $filePath = $up['path'];
            }
        } elseif ($fileType === 'media') {
            $libId = (int)($_POST['library_media_id'] ?? 0);
            if ($libId) {
                $lib = Database::fetchOne("SELECT * FROM media_library WHERE id = ? AND org_id = ?", [$libId, $orgId]);
                if ($lib) $filePath = $lib['path'];
            }
        }

        if ($banner['status'] !== $status) Audit::log('modified', 'banner', $name, 'Actif', $banner['status'], $status);

        Database::execute(
            "UPDATE banners SET name=?, file_type=?, file_path=?, url=?, status=?, catalog_id=? WHERE id=? AND org_id=?",
            [$name, $fileType, $filePath, $url, $status, $catalogId, $id, $orgId]
        );
        echo json_encode(['success' => true]);
    }

    public function get(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $banner = Database::fetchOne(
            "SELECT b.*, c.name as catalog_name FROM banners b LEFT JOIN catalogs c ON b.catalog_id = c.id WHERE b.id = ? AND b.org_id = ?",
            [$id, $orgId]
        );
        if ($banner && $banner['file_path']) {
            $banner['file_url'] = UPLOAD_URL . $banner['file_path'];
        }
        echo json_encode($banner);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $banner = Database::fetchOne("SELECT * FROM banners WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if ($banner) {
            Audit::log('deleted', 'banner', $banner['name']);
            Database::execute("UPDATE banners SET archived_at = NOW() WHERE id = ? AND org_id = ?", [$id, $orgId]);
        }
        echo json_encode(['success' => true]);
    }

    public function reorder(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $items = json_decode(file_get_contents('php://input'), true)['items'] ?? [];
        foreach ($items as $item) {
            Database::execute("UPDATE banners SET position = ? WHERE id = ? AND org_id = ?", [(int)$item['position'], (int)$item['id'], $orgId]);
        }
        echo json_encode(['success' => true]);
    }
}
