<?php
class LibraryController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $filter = $_GET['type'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 24;
        $offset = ($page - 1) * $limit;

        $where = "WHERE org_id = ?";
        $queryParams = [$orgId];

        if ($filter !== 'all') {
            $where .= " AND file_type = ?";
            $queryParams[] = $filter;
        }
        if (!empty($search)) {
            $where .= " AND original_name LIKE ?";
            $queryParams[] = '%' . $search . '%';
        }

        $media = Database::fetchAll(
            "SELECT * FROM media_library {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?",
            array_merge($queryParams, [$limit, $offset])
        );
        $total = Database::fetchOne("SELECT COUNT(*) as c FROM media_library {$where}", $queryParams);
        $totalCount = $total['c'] ?? 0;

        // Stats by type
        $stats = Database::fetchAll(
            "SELECT file_type, COUNT(*) as count, SUM(file_size) as total_size FROM media_library WHERE org_id = ? GROUP BY file_type",
            [$orgId]
        );

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/library/index.php';
    }

    public function upload(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();

        if (empty($_FILES['file']['name'])) {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
            return;
        }

        $up = Upload::handle($_FILES['file'], 'library', 'media');
        if (!$up['success']) { echo json_encode($up); return; }

        $adminProtected = Auth::isAdmin() ? 1 : 0;
        $id = Database::insert(
            "INSERT INTO media_library (org_id, filename, original_name, file_type, mime_type, file_size, path, admin_protected) VALUES (?,?,?,?,?,?,?,?)",
            [$orgId, $up['filename'], $up['original_name'], Upload::mediaType($up['mime']), $up['mime'], $up['size'], $up['path'], $adminProtected]
        );

        echo json_encode(['success' => true, 'id' => $id, 'url' => $up['url']]);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);

        $media = Database::fetchOne("SELECT * FROM media_library WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if ($media) {
            $currentUser = Auth::user();
            if (!empty($media['admin_protected']) && $currentUser['role'] === 'user') {
                echo json_encode(['success' => false, 'error' => 'Ce contenu a été ajouté par un administrateur et ne peut pas être supprimé.']);
                return;
            }
            Upload::delete($media['path']);
            Database::execute("DELETE FROM media_library WHERE id = ? AND org_id = ?", [$id, $orgId]);
        }
        echo json_encode(['success' => true]);
    }
}
