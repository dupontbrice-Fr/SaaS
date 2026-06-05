<?php
class ScreensaverController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $screensavers = Database::fetchAll(
            "SELECT * FROM screensavers WHERE org_id = ? ORDER BY position ASC, created_at DESC",
            [$orgId]
        );
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/screensaver/index.php';
    }

    public function add(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $name = trim($_POST['name'] ?? '');
        $fileType = $_POST['file_type'] ?? 'media';
        $status = $_POST['status'] ?? 'active';
        $url = trim($_POST['url'] ?? '');

        if (empty($name)) { echo json_encode(['success' => false, 'error' => 'Le titre est requis']); return; }

        $filePath = null;
        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'screensavers', 'media');
            if (!$up['success']) { echo json_encode($up); return; }
            $filePath = $up['path'];
            Database::execute(
                "INSERT INTO media_library (org_id, filename, original_name, file_type, mime_type, file_size, path, used_in) VALUES (?,?,?,?,?,?,?,?)",
                [$orgId, $up['filename'], $up['original_name'], Upload::mediaType($up['mime']), $up['mime'], $up['size'], $up['path'], 'screensaver']
            );
        }

        $maxPos = Database::fetchOne("SELECT MAX(position) as m FROM screensavers WHERE org_id = ?", [$orgId]);
        $id = Database::insert(
            "INSERT INTO screensavers (org_id, name, file_type, file_path, url, status, position) VALUES (?,?,?,?,?,?,?)",
            [$orgId, $name, $fileType, $filePath, $url, $status, ($maxPos['m'] ?? 0) + 1]
        );

        Audit::log('created', 'screensaver', $name);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function edit(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $ss = Database::fetchOne("SELECT * FROM screensavers WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$ss) { echo json_encode(['success' => false]); return; }

        $name = trim($_POST['name'] ?? $ss['name']);
        $fileType = $_POST['file_type'] ?? $ss['file_type'];
        $status = $_POST['status'] ?? $ss['status'];
        $url = trim($_POST['url'] ?? $ss['url'] ?? '');
        $filePath = $ss['file_path'];

        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'screensavers', 'media');
            if ($up['success']) { if ($filePath) Upload::delete($filePath); $filePath = $up['path']; }
        }

        Database::execute("UPDATE screensavers SET name=?, file_type=?, file_path=?, url=?, status=? WHERE id=? AND org_id=?",
            [$name, $fileType, $filePath, $url, $status, $id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function get(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        echo json_encode(Database::fetchOne("SELECT * FROM screensavers WHERE id = ? AND org_id = ?", [$id, $orgId]));
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $ss = Database::fetchOne("SELECT * FROM screensavers WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if ($ss) {
            if ($ss['file_path']) Upload::delete($ss['file_path']);
            Audit::log('deleted', 'screensaver', $ss['name']);
            Database::execute("DELETE FROM screensavers WHERE id = ? AND org_id = ?", [$id, $orgId]);
        }
        echo json_encode(['success' => true]);
    }
}
