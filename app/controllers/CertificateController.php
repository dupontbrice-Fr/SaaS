<?php
class CertificateController {
    public function index(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'updated_at';

        $certs = Database::fetchAll(
            "SELECT c.*, p.status as product_status, p.thumbnail, p.file_type, p.file_path FROM certificates c
             LEFT JOIN products p ON c.product_id = p.id
             WHERE c.org_id = ? ORDER BY c.updated_at DESC LIMIT ? OFFSET ?",
            [$orgId, $limit, $offset]
        );
        $total = Database::fetchOne("SELECT COUNT(*) as c FROM certificates WHERE org_id = ?", [$orgId]);

        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/certificates/index.php';
    }

    public function generate(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $productId = (int)($params['id'] ?? 0);

        $product = Database::fetchOne("SELECT * FROM products WHERE id = ? AND org_id = ?", [$productId, $orgId]);
        if (!$product) {
            echo json_encode(['success' => false, 'error' => 'Produit introuvable']);
            return;
        }

        // Create or update certificate record
        $existing = Database::fetchOne("SELECT * FROM certificates WHERE product_id = ? AND org_id = ?", [$productId, $orgId]);
        if ($existing) {
            Database::execute("UPDATE certificates SET updated_at = NOW() WHERE id = ?", [$existing['id']]);
        } else {
            Database::insert(
                "INSERT INTO certificates (org_id, product_id, product_name, created_by) VALUES (?,?,?,?)",
                [$orgId, $productId, $product['name'], Auth::userEmail()]
            );
        }

        Audit::log('created', 'certificate', $product['name']);
        echo json_encode(['success' => true, 'product_id' => $productId]);
    }

    public function download(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        $productId = (int)($params['id'] ?? 0);

        $product = Database::fetchOne("SELECT * FROM products WHERE id = ? AND org_id = ?", [$productId, $orgId]);
        $cert = Database::fetchOne("SELECT * FROM certificates WHERE product_id = ? AND org_id = ?", [$productId, $orgId]);
        if (!$product) { http_response_code(404); exit; }

        $product['cert_updated_at'] = $cert['updated_at'] ?? $cert['created_at'] ?? date('Y-m-d H:i:s');

        $history = Database::fetchAll(
            "SELECT * FROM product_history WHERE product_id = ? ORDER BY created_at ASC",
            [$productId]
        );

        $org = Database::fetchOne("SELECT name FROM organizations WHERE id = ?", [$orgId]);
        CertificatePDF::generate($product, $history, $org['name'] ?? 'MediaPush', Auth::userEmail());
    }

    public function downloadZip(array $params = []): void {
        Auth::require();
        $orgId = Auth::orgId();
        // Return info about all certs - simplified for now
        header('Content-Type: application/json');
        $certs = Database::fetchAll("SELECT * FROM certificates WHERE org_id = ?", [$orgId]);
        echo json_encode(['count' => count($certs), 'message' => 'Export ZIP non disponible en mode démo']);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $cert = Database::fetchOne("SELECT * FROM certificates WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if ($cert) {
            Audit::log('deleted', 'certificate', $cert['product_name']);
            Database::execute("DELETE FROM certificates WHERE id = ? AND org_id = ?", [$id, $orgId]);
        }
        echo json_encode(['success' => true]);
    }
}
