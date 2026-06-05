<?php
class UserController {
    public function index(array $params = []): void {
        Auth::require();
        if (!Auth::isAdmin()) {
            header('Location: /manage/catalog');
            exit;
        }
        $orgId = Auth::orgId();
        $currentUser = Auth::user();
        $users = Database::fetchAll(
            "SELECT id, email, role, created_at FROM users WHERE org_id = ? ORDER BY created_at DESC",
            [$orgId]
        );
        include __DIR__ . '/../views/layout.php';
        include __DIR__ . '/../views/users/index.php';
    }

    public function create(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        if (!Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']);
            return;
        }
        $orgId = Auth::orgId();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email et mot de passe requis']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Adresse email invalide']);
            return;
        }
        if (!in_array($role, ['admin', 'user'])) {
            $role = 'user';
        }
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères']);
            return;
        }

        $existing = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Cet email est déjà utilisé']);
            return;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $id = Database::insert(
            "INSERT INTO users (org_id, email, password, role) VALUES (?,?,?,?)",
            [$orgId, $email, $hashed, $role]
        );

        Audit::log('created', 'user', $email);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function delete(array $params = []): void {
        Auth::require();
        header('Content-Type: application/json');
        if (!Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Accès refusé']);
            return;
        }
        $orgId = Auth::orgId();
        $id = (int)($params['id'] ?? 0);
        $currentUser = Auth::user();

        if ($id === (int)$currentUser['id']) {
            echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas supprimer votre propre compte']);
            return;
        }

        $user = Database::fetchOne("SELECT * FROM users WHERE id = ? AND org_id = ?", [$id, $orgId]);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
            return;
        }
        if ($user['role'] === 'superadmin') {
            echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le super administrateur']);
            return;
        }

        Database::execute("DELETE FROM users WHERE id = ? AND org_id = ?", [$id, $orgId]);
        Audit::log('deleted', 'user', $user['email']);
        echo json_encode(['success' => true]);
    }
}
