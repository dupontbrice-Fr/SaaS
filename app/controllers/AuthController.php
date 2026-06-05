<?php
class AuthController {
    public function loginPage(array $params = []): void {
        if (Auth::check()) {
            header('Location: /manage/catalog');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        include __DIR__ . '/../views/auth/login.php';
    }

    public function loginPost(array $params = []): void {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password)) {
            header('Location: /manage/catalog');
            exit;
        }

        $_SESSION['login_error'] = 'Email ou mot de passe incorrect';
        header('Location: /login');
        exit;
    }

    public function logout(array $params = []): void {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
