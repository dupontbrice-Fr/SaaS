<?php
class LibraryController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId   = Auth::orgId();
        $filter  = $_GET['type']   ?? 'all';
        $search  = $_GET['search'] ?? '';
        $subView = $_GET['sub']    ?? 'library';
        $isArchive = ($subView === 'archives');
        $page   = (int)($_GET['page'] ?? 1);
        $limit  = 24;
        $offset = ($page - 1) * $limit;

        // Migrations
        try {
            Database::execute("ALTER TABLE media_library ADD COLUMN IF NOT EXISTS archived_at  TIMESTAMP    NULL DEFAULT NULL");
            Database::execute("ALTER TABLE media_library ADD COLUMN IF NOT EXISTS uploaded_by  VARCHAR(255) NULL DEFAULT NULL");
        } catch (\Exception $e) {}

        $where = $isArchive
            ? "WHERE org_id = ? AND archived_at IS NOT NULL"
            : "WHERE org_id = ? AND archived_at IS NULL";
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
        $total      = Database::fetchOne("SELECT COUNT(*) as c FROM media_library {$where}", $queryParams);
        $totalCount = $total['c'] ?? 0;

        // Stats — non-archived only
        $stats = Database::fetchAll(
            "SELECT file_type, COUNT(*) as count, SUM(file_size) as total_size FROM media_library WHERE org_id = ? AND archived_at IS NULL GROUP BY file_type",
            [$orgId]
        );
        $archivedCount = Database::fetchOne(
            "SELECT COUNT(*) as c FROM media_library WHERE org_id = ? AND archived_at IS NOT NULL",
            [$orgId]
        )['c'] ?? 0;

        // Linkage map: path → [{type, name}]
        $linkageMap = [];
        if (!empty($media)) {
            $paths = array_values(array_filter(array_column($media, 'path')));
            if (!empty($paths)) {
                $ph = implode(',', array_fill(0, count($paths), '?'));

                $queries = [
                    ["SELECT file_path as p, name, 'Produit'        as t FROM products    WHERE org_id=? AND file_path  IN ($ph) AND archived_at IS NULL", array_merge([$orgId], $paths)],
                    ["SELECT thumbnail  as p, name, 'Miniature'      as t FROM products    WHERE org_id=? AND thumbnail   IN ($ph) AND archived_at IS NULL", array_merge([$orgId], $paths)],
                    ["SELECT image      as p, name, 'Catégorie'      as t FROM categories  WHERE org_id=? AND image        IN ($ph) AND archived_at IS NULL", array_merge([$orgId], $paths)],
                    ["SELECT file_path  as p, name, 'Bannière'       as t FROM banners      WHERE org_id=? AND file_path  IN ($ph)", array_merge([$orgId], $paths)],
                    ["SELECT file_path  as p, name, 'Écran de veille' as t FROM screensavers WHERE org_id=? AND file_path  IN ($ph)", array_merge([$orgId], $paths)],
                ];
                foreach ($queries as [$sql, $args]) {
                    foreach (Database::fetchAll($sql, $args) as $row) {
                        if ($row['p']) $linkageMap[$row['p']][] = ['type' => $row['t'], 'name' => $row['name']];
                    }
                }
            }
        }

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
        $uploadedBy     = Auth::userEmail();
        $id = Database::insert(
            "INSERT INTO media_library (org_id, filename, original_name, file_type, mime_type, file_size, path, admin_protected, uploaded_by) VALUES (?,?,?,?,?,?,?,?,?)",
            [$orgId, $up['filename'], $up['original_name'], Upload::mediaType($up['mime']), $up['mime'], $up['size'], $up['path'], $adminProtected, $uploadedBy]
        );

        echo json_encode(['success' => true, 'id' => $id, 'url' => $up['url']]);
    }

    public function archive(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);

        $media = Database::fetchOne("SELECT * FROM media_library WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$media) { echo json_encode(['success' => false, 'error' => 'Fichier introuvable']); return; }

        if (!empty($media['admin_protected']) && !Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Ce contenu est protégé et ne peut pas être archivé.']);
            return;
        }
        Database::execute("UPDATE media_library SET archived_at = NOW() WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function restore(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        Database::execute("UPDATE media_library SET archived_at = NULL WHERE id = ? AND org_id = ?", [$id, $orgId]);
        echo json_encode(['success' => true]);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);

        $media = Database::fetchOne("SELECT * FROM media_library WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if ($media) {
            if (!empty($media['admin_protected']) && !Auth::isAdmin()) {
                echo json_encode(['success' => false, 'error' => 'Ce contenu ne peut pas être supprimé.']);
                return;
            }
            Upload::delete($media['path']);
            Database::execute("DELETE FROM media_library WHERE id = ? AND org_id = ?", [$id, $orgId]);
        }
        echo json_encode(['success' => true]);
    }

    // ── Actions groupées ─────────────────────────────────────────

    public function bulkArchive(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $ids   = array_map('intval', json_decode(file_get_contents('php://input'), true)['ids'] ?? []);
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'Aucun ID']); return; }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $extra = Auth::isAdmin() ? '' : " AND (admin_protected = 0 OR admin_protected IS NULL)";
        Database::execute(
            "UPDATE media_library SET archived_at = NOW() WHERE id IN ($ph) AND org_id = ?$extra",
            array_merge($ids, [$orgId])
        );
        echo json_encode(['success' => true]);
    }

    public function bulkRestore(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $ids   = array_map('intval', json_decode(file_get_contents('php://input'), true)['ids'] ?? []);
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'Aucun ID']); return; }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        Database::execute(
            "UPDATE media_library SET archived_at = NULL WHERE id IN ($ph) AND org_id = ?",
            array_merge($ids, [$orgId])
        );
        echo json_encode(['success' => true]);
    }

    public function bulkDelete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $ids   = array_map('intval', json_decode(file_get_contents('php://input'), true)['ids'] ?? []);
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'Aucun ID']); return; }

        $ph    = implode(',', array_fill(0, count($ids), '?'));
        $extra = Auth::isAdmin() ? '' : " AND (admin_protected = 0 OR admin_protected IS NULL)";
        $files = Database::fetchAll("SELECT * FROM media_library WHERE id IN ($ph) AND org_id = ?$extra", array_merge($ids, [$orgId]));
        foreach ($files as $f) { Upload::delete($f['path']); }
        if (!empty($files)) {
            $delIds = array_column($files, 'id');
            $ph2    = implode(',', array_fill(0, count($delIds), '?'));
            Database::execute("DELETE FROM media_library WHERE id IN ($ph2)", $delIds);
        }
        echo json_encode(['success' => true]);
    }
}
