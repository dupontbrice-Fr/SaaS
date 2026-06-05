<?php
class CatalogController {

    private function archiveCategoryTree(int $catId, int $orgId): void {
        Database::execute("UPDATE products SET archived_at=NOW() WHERE category_id=? AND org_id=? AND archived_at IS NULL", [$catId, $orgId]);
        $subcats = Database::fetchAll("SELECT id FROM categories WHERE parent_id=? AND org_id=? AND archived_at IS NULL", [$catId, $orgId]);
        foreach ($subcats as $sub) {
            $this->archiveCategoryTree((int)$sub['id'], $orgId);
        }
        Database::execute("UPDATE categories SET archived_at=NOW() WHERE id=? AND org_id=?", [$catId, $orgId]);
    }

    private function getBreadcrumb(int $categoryId, int $orgId): array {
        $breadcrumb = [];
        $id = $categoryId;
        $safety = 0;
        while ($id && $safety++ < 10) {
            $cat = Database::fetchOne("SELECT id, name, parent_id FROM categories WHERE id = ? AND org_id = ?", [$id, $orgId]);
            if (!$cat) break;
            array_unshift($breadcrumb, $cat);
            $id = (int)($cat['parent_id'] ?? 0);
        }
        return $breadcrumb;
    }

    public function index(array $params = []): void {
        Auth::require();
        $orgId   = Auth::orgId();
        $filter  = $_GET['filter'] ?? 'all';
        $subView = $_GET['sub']    ?? 'catalog';
        $currentCategory = null;
        $currentCatalog  = null;
        $breadcrumb      = [];

        // Catalog list view (top-level)
        $catalogs = Database::fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM categories cat WHERE cat.catalog_id=c.id AND cat.archived_at IS NULL) as cat_count
             FROM catalogs c WHERE c.org_id=? ORDER BY c.name ASC",
            [$orgId]
        );

        // Licenses + their screens for the device panel
        $licensesForPanel = Database::fetchAll(
            "SELECT l.*, s.id as screen_id, s.name as screen_name, s.status as screen_status, s.catalog_id as screen_catalog_id
             FROM licenses l LEFT JOIN screens s ON s.license_id=l.id
             WHERE l.org_id=? AND l.active=1 ORDER BY l.code ASC",
            [$orgId]
        );

        if ($subView === 'archives') {
            $categories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND archived_at IS NOT NULL ORDER BY archived_at DESC", [$orgId]);
            $products   = Database::fetchAll("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.org_id=? AND p.archived_at IS NOT NULL ORDER BY p.archived_at DESC", [$orgId]);
        } else {
            $categories = [];
            $products   = [];
        }

        $allCategories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND archived_at IS NULL ORDER BY name ASC", [$orgId]);
        $allCatalogs   = $catalogs;

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/catalog/index.php';
    }

    public function catalogCategories(array $params = []): void {
        Auth::require();
        $orgId     = Auth::orgId();
        $catalogId = (int)($params['id'] ?? 0);
        $filter    = $_GET['filter'] ?? 'all';
        $subView   = $_GET['sub']    ?? 'catalog';

        $currentCatalog = ($catalogId > 0)
            ? Database::fetchOne("SELECT * FROM catalogs WHERE id=? AND org_id=?", [$catalogId, $orgId])
            : ['id' => 0, 'name' => 'Sans catalogue'];
        if ($catalogId > 0 && !$currentCatalog) { header('Location: /manage/catalog'); exit; }

        $currentCategory = null;
        $breadcrumb      = [];

        if ($subView === 'archives') {
            $filter_cat = $catalogId > 0 ? "AND cat.catalog_id=?" : "AND cat.catalog_id IS NULL";
            $categories = Database::fetchAll("SELECT cat.* FROM categories cat WHERE cat.org_id=? {$filter_cat} AND cat.archived_at IS NOT NULL ORDER BY cat.archived_at DESC", $catalogId > 0 ? [$orgId, $catalogId] : [$orgId]);
            $products   = [];
        } else {
            $filter_cat = $catalogId > 0 ? "AND parent_id IS NULL AND catalog_id=?" : "AND parent_id IS NULL AND catalog_id IS NULL";
            $categories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? {$filter_cat} AND archived_at IS NULL ORDER BY position ASC, created_at DESC", $catalogId > 0 ? [$orgId, $catalogId] : [$orgId]);
            $products   = [];
        }

        $catalogs          = [];
        $licensesForPanel  = [];
        $allCategories     = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND archived_at IS NULL ORDER BY name ASC", [$orgId]);
        $allCatalogs       = Database::fetchAll("SELECT id, name FROM catalogs WHERE org_id=? ORDER BY name ASC", [$orgId]);

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/catalog/index.php';
    }

    public function browse(array $params = []): void {
        Auth::require();
        $orgId      = Auth::orgId();
        $categoryId = (int)($params['id'] ?? 0);
        $filter  = $_GET['filter'] ?? 'all';
        $subView = $_GET['sub']    ?? 'catalog';

        $currentCategory = Database::fetchOne("SELECT * FROM categories WHERE id=? AND org_id=?", [$categoryId, $orgId]);
        if (!$currentCategory) { header('Location: /manage/catalog'); exit; }

        $breadcrumb = $this->getBreadcrumb($categoryId, $orgId);
        $currentCatalog  = null;
        $catalogs        = [];
        $licensesForPanel = [];

        if ($subView === 'archives') {
            $categories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND parent_id=? AND archived_at IS NOT NULL ORDER BY archived_at DESC", [$orgId, $categoryId]);
            $products   = Database::fetchAll("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.org_id=? AND p.category_id=? AND p.archived_at IS NOT NULL ORDER BY p.archived_at DESC", [$orgId, $categoryId]);
        } else {
            $categories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND parent_id=? AND archived_at IS NULL ORDER BY position ASC", [$orgId, $categoryId]);
            $products   = Database::fetchAll("SELECT * FROM products WHERE org_id=? AND category_id=? AND archived_at IS NULL ORDER BY position ASC", [$orgId, $categoryId]);
        }
        $allCategories = Database::fetchAll("SELECT * FROM categories WHERE org_id=? AND archived_at IS NULL ORDER BY name ASC", [$orgId]);
        $allCatalogs   = Database::fetchAll("SELECT id, name FROM catalogs WHERE org_id=? ORDER BY name ASC", [$orgId]);

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/catalog/index.php';
    }

    // ── Catalog CRUD ──────────────────────────────────────────────

    public function createCatalog(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $name  = trim($_POST['name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if (empty($name)) { echo json_encode(['success' => false, 'error' => 'Nom requis']); return; }
        $id = Database::insert("INSERT INTO catalogs (org_id, name, description) VALUES (?,?,?)", [$orgId, $name, $desc]);
        Audit::log('created', 'catalog', $name);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function editCatalog(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        $cat   = Database::fetchOne("SELECT * FROM catalogs WHERE id=? AND org_id=?", [$id, $orgId]);
        if (!$cat) { echo json_encode(['success' => false]); return; }
        $name = trim($_POST['name'] ?? $cat['name']);
        $desc = trim($_POST['description'] ?? $cat['description'] ?? '');
        Database::execute("UPDATE catalogs SET name=?, description=? WHERE id=? AND org_id=?", [$name, $desc, $id, $orgId]);
        Audit::log('modified', 'catalog', $name);
        echo json_encode(['success' => true]);
    }

    public function deleteCatalog(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        $cat   = Database::fetchOne("SELECT * FROM catalogs WHERE id=? AND org_id=?", [$id, $orgId]);
        if (!$cat) { echo json_encode(['success' => false]); return; }
        // Detach categories (set catalog_id to NULL)
        Database::execute("UPDATE categories SET catalog_id=NULL WHERE catalog_id=? AND org_id=?", [$id, $orgId]);
        Database::execute("DELETE FROM catalogs WHERE id=? AND org_id=?", [$id, $orgId]);
        Audit::log('deleted', 'catalog', $cat['name']);
        echo json_encode(['success' => true]);
    }

    public function getCatalog(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        echo json_encode(Database::fetchOne("SELECT * FROM catalogs WHERE id=? AND org_id=?", [$id, $orgId]) ?: ['error' => 'Not found']);
    }

    public function assignScreenCatalog(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId     = Auth::orgId();
        $screenId  = (int)($params['screen_id'] ?? 0);
        $catalogId = !empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null;
        $screen    = Database::fetchOne("SELECT * FROM screens WHERE id=? AND org_id=?", [$screenId, $orgId]);
        if (!$screen) { echo json_encode(['success' => false, 'error' => 'Écran introuvable']); return; }
        if ($catalogId) {
            $catalog = Database::fetchOne("SELECT * FROM catalogs WHERE id=? AND org_id=?", [$catalogId, $orgId]);
            if (!$catalog) { echo json_encode(['success' => false, 'error' => 'Catalogue introuvable']); return; }
        }
        Database::execute("UPDATE screens SET catalog_id=? WHERE id=? AND org_id=?", [$catalogId, $screenId, $orgId]);
        echo json_encode(['success' => true]);
    }

    // ── Category CRUD ─────────────────────────────────────────────

    public function addCategory(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId    = Auth::orgId();
        $name      = trim($_POST['name'] ?? '');
        $parentId  = !empty($_POST['parent_id'])  ? (int)$_POST['parent_id']  : null;
        $catalogId = !empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null;
        $status    = $_POST['status'] ?? 'active';
        $showTitle   = isset($_POST['show_title'])        ? 1 : 0;
        $viewableExt = isset($_POST['viewable_external']) ? 1 : 0;

        if (empty($name)) { echo json_encode(['success'=>false,'error'=>'Le titre est requis']); return; }

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $up = Upload::handle($_FILES['image'], 'categories', 'image');
            if (!$up['success']) { echo json_encode($up); return; }
            $imagePath = $up['path'];
        }

        $maxPos = Database::fetchOne("SELECT MAX(position) AS m FROM categories WHERE org_id=? AND parent_id " . ($parentId ? "= ?" : "IS NULL"), array_filter([$orgId, $parentId]));
        $id = Database::insert(
            "INSERT INTO categories (org_id,catalog_id,parent_id,name,image,status,show_title,viewable_external,position) VALUES(?,?,?,?,?,?,?,?,?)",
            [$orgId, $catalogId, $parentId, $name, $imagePath, $status, $showTitle, $viewableExt, ($maxPos['m']??0)+1]
        );
        Audit::log('created','category',$name);
        echo json_encode(['success'=>true,'id'=>$id]);
    }

    public function getCategory(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        echo json_encode(Database::fetchOne("SELECT * FROM categories WHERE id=? AND org_id=?", [$id,$orgId]) ?: ['error'=>'Not found']);
    }

    public function editCategory(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        $cat   = Database::fetchOne("SELECT * FROM categories WHERE id=? AND org_id=?", [$id,$orgId]);
        if (!$cat) { echo json_encode(['success'=>false]); return; }

        $name        = trim($_POST['name'] ?? $cat['name']);
        $status      = $_POST['status']   ?? $cat['status'];
        $showTitle   = isset($_POST['show_title'])        ? 1 : 0;
        $viewableExt = isset($_POST['viewable_external']) ? 1 : 0;
        $imagePath   = $cat['image'];
        $catalogId   = array_key_exists('catalog_id', $_POST)
            ? (!empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null)
            : $cat['catalog_id'];

        if (!empty($_FILES['image']['name'])) {
            $up = Upload::handle($_FILES['image'], 'categories', 'image');
            if ($up['success']) { if ($imagePath) Upload::delete($imagePath); $imagePath = $up['path']; }
        }

        if ($cat['name'] !== $name)     Audit::log('modified','category',$name,'Nom',$cat['name'],$name);
        if ($cat['status'] !== $status) Audit::log('modified','category',$name,'Statut',$cat['status'],$status);

        Database::execute("UPDATE categories SET name=?,image=?,status=?,show_title=?,viewable_external=?,catalog_id=? WHERE id=? AND org_id=?",
            [$name,$imagePath,$status,$showTitle,$viewableExt,$catalogId,$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    public function deleteCategory(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id    = (int)($params['id'] ?? 0);
        $cat   = Database::fetchOne("SELECT * FROM categories WHERE id=? AND org_id=?", [$id,$orgId]);
        if (!$cat) { echo json_encode(['success'=>false]); return; }
        $this->archiveCategoryTree($id, $orgId);
        Audit::log('deleted','category',$cat['name']);
        echo json_encode(['success'=>true]);
    }

    public function restoreCategory(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        Database::execute("UPDATE categories SET archived_at=NULL WHERE id=? AND org_id=?",[$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    public function hardDeleteCategory(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        $cat = Database::fetchOne("SELECT * FROM categories WHERE id=? AND org_id=?",[$id,$orgId]);
        if ($cat && $cat['image']) Upload::delete($cat['image']);
        Database::execute("DELETE FROM categories WHERE id=? AND org_id=?",[$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    // ── Product CRUD ──────────────────────────────────────────────

    public function addProduct(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId      = Auth::orgId();
        $name       = trim($_POST['name'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $fileType   = $_POST['file_type']  ?? 'media';
        $status     = $_POST['status']     ?? 'active';
        $downloadable  = isset($_POST['downloadable'])       ? 1 : 0;
        $showTitle     = isset($_POST['show_title'])         ? 1 : 0;
        $viewableExt   = isset($_POST['viewable_external'])  ? 1 : 0;
        $enableCache   = isset($_POST['enable_cache'])       ? 1 : 0;
        $protectedNav  = isset($_POST['protected_nav'])      ? 1 : 0;
        $url           = trim($_POST['url'] ?? '');

        if (empty($name))   { echo json_encode(['success'=>false,'error'=>'Le titre est requis']); return; }
        if (!$categoryId)   { echo json_encode(['success'=>false,'error'=>'Veuillez sélectionner une catégorie']); return; }

        $filePath = null; $fileMime = null; $thumbPath = null;

        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'products', 'media');
            if (!$up['success']) { echo json_encode($up); return; }
            $filePath = $up['path']; $fileMime = $up['mime'];
            Database::execute("INSERT INTO media_library (org_id,filename,original_name,file_type,mime_type,file_size,path,used_in) VALUES(?,?,?,?,?,?,?,?)",
                [$orgId,$up['filename'],$up['original_name'],Upload::mediaType($up['mime']),$up['mime'],$up['size'],$up['path'],'product']);
        }
        if (!empty($_FILES['thumbnail']['name'])) {
            $up = Upload::handle($_FILES['thumbnail'], 'products/thumbnails', 'image');
            if ($up['success']) $thumbPath = $up['path'];
        }

        $maxPos = Database::fetchOne("SELECT MAX(position) AS m FROM products WHERE org_id=? AND category_id=?", [$orgId,$categoryId]);
        $id = Database::insert(
            "INSERT INTO products (org_id,category_id,name,thumbnail,file_type,file_path,file_mime,url,status,downloadable,show_title,viewable_external,enable_cache,protected_nav,position) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$orgId,$categoryId,$name,$thumbPath,$fileType,$filePath,$fileMime,$url,$status,$downloadable,$showTitle,$viewableExt,$enableCache,$protectedNav,($maxPos['m']??0)+1]
        );

        Audit::log('created','product',$name);
        Audit::logProductHistory((int)$id,'Création',null,$name);
        echo json_encode(['success'=>true,'id'=>$id]);
    }

    public function getProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        echo json_encode(Database::fetchOne("SELECT * FROM products WHERE id=? AND org_id=?",[$id,$orgId]) ?: ['error'=>'Not found']);
    }

    public function editProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        $prod  = Database::fetchOne("SELECT * FROM products WHERE id=? AND org_id=?",[$id,$orgId]);
        if (!$prod) { echo json_encode(['success'=>false]); return; }

        $name       = trim($_POST['name']  ?? $prod['name']);
        $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : $prod['category_id'];
        $fileType   = $_POST['file_type']  ?? $prod['file_type'];
        $status     = $_POST['status']     ?? $prod['status'];
        $downloadable  = isset($_POST['downloadable'])       ? 1 : 0;
        $showTitle     = isset($_POST['show_title'])         ? 1 : 0;
        $viewableExt   = isset($_POST['viewable_external'])  ? 1 : 0;
        $enableCache   = isset($_POST['enable_cache'])       ? 1 : 0;
        $protectedNav  = isset($_POST['protected_nav'])      ? 1 : 0;
        $url           = trim($_POST['url'] ?? $prod['url'] ?? '');
        $filePath  = $prod['file_path']; $fileMime = $prod['file_mime']; $thumbPath = $prod['thumbnail'];

        if ($fileType === 'media' && !empty($_FILES['file']['name'])) {
            $up = Upload::handle($_FILES['file'], 'products', 'media');
            if ($up['success']) { if ($filePath) Upload::delete($filePath); $filePath=$up['path']; $fileMime=$up['mime']; }
        }
        if (!empty($_FILES['thumbnail']['name'])) {
            $up = Upload::handle($_FILES['thumbnail'], 'products/thumbnails', 'image');
            if ($up['success']) { if ($thumbPath) Upload::delete($thumbPath); $thumbPath=$up['path']; }
        }

        foreach (['name'=>'Nom','status'=>'Statut'] as $f=>$l) {
            if ((string)$prod[$f] !== (string)$$f) {
                Audit::log('modified','product',$name,$l,(string)$prod[$f],(string)$$f);
                Audit::logProductHistory($id,$l,(string)$prod[$f],(string)$$f);
            }
        }

        Database::execute("UPDATE products SET name=?,category_id=?,thumbnail=?,file_type=?,file_path=?,file_mime=?,url=?,status=?,downloadable=?,show_title=?,viewable_external=?,enable_cache=?,protected_nav=? WHERE id=? AND org_id=?",
            [$name,$categoryId,$thumbPath,$fileType,$filePath,$fileMime,$url,$status,$downloadable,$showTitle,$viewableExt,$enableCache,$protectedNav,$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    public function deleteProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        $prod = Database::fetchOne("SELECT * FROM products WHERE id=? AND org_id=?",[$id,$orgId]);
        if (!$prod) { echo json_encode(['success'=>false]); return; }
        Database::execute("UPDATE products SET archived_at=NOW() WHERE id=? AND org_id=?",[$id,$orgId]);
        Audit::log('deleted','product',$prod['name']);
        echo json_encode(['success'=>true]);
    }

    public function restoreProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        Database::execute("UPDATE products SET archived_at=NULL WHERE id=? AND org_id=?",[$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    public function hardDeleteProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        $prod = Database::fetchOne("SELECT * FROM products WHERE id=? AND org_id=?",[$id,$orgId]);
        if ($prod) { if ($prod['file_path']) Upload::delete($prod['file_path']); if ($prod['thumbnail']) Upload::delete($prod['thumbnail']); }
        Database::execute("DELETE FROM products WHERE id=? AND org_id=?",[$id,$orgId]);
        echo json_encode(['success'=>true]);
    }

    public function duplicateProduct(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId(); $id = (int)($params['id']??0);
        $prod = Database::fetchOne("SELECT * FROM products WHERE id=? AND org_id=?",[$id,$orgId]);
        if (!$prod) { echo json_encode(['success'=>false]); return; }
        $maxPos = Database::fetchOne("SELECT MAX(position) AS m FROM products WHERE org_id=? AND category_id=?",[$orgId,$prod['category_id']]);
        $newId = Database::insert(
            "INSERT INTO products (org_id,category_id,name,thumbnail,file_type,file_path,file_mime,url,status,downloadable,show_title,viewable_external,enable_cache,protected_nav,position) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$orgId,$prod['category_id'],$prod['name'].' (copie)',$prod['thumbnail'],$prod['file_type'],$prod['file_path'],$prod['file_mime'],$prod['url'],$prod['status'],$prod['downloadable'],$prod['show_title'],$prod['viewable_external'],$prod['enable_cache'],$prod['protected_nav'],($maxPos['m']??0)+1]
        );
        Audit::log('created','product',$prod['name'].' (copie)');
        echo json_encode(['success'=>true,'id'=>$newId]);
    }

    public function reorder(array $params = []): void {
        Auth::require(); header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $items = json_decode(file_get_contents('php://input'), true)['items'] ?? [];
        foreach ($items as $item) {
            $table = ($item['type']??'product') === 'category' ? 'categories' : 'products';
            Database::execute("UPDATE {$table} SET position=? WHERE id=? AND org_id=?",[(int)$item['position'],(int)$item['id'],$orgId]);
        }
        echo json_encode(['success'=>true]);
    }
}
