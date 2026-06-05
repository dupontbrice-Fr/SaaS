<?php
class Auth {
    public static function login(string $email, string $password): bool {
        $user = Database::fetchOne(
            "SELECT u.*, o.name as org_name, o.slug as org_slug FROM users u JOIN organizations o ON u.org_id = o.id WHERE u.email = ?",
            [$email]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Load settings
        $settings = Database::fetchOne("SELECT * FROM settings WHERE org_id = ?", [$user['org_id']]);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'org_id' => $user['org_id'],
            'org_name' => $user['org_name'],
            'org_slug' => $user['org_slug'],
        ];
        $_SESSION['settings'] = $settings ?: [];

        // Log login
        Database::execute(
            "INSERT INTO login_history (org_id, user_email, url, ip) VALUES (?, ?, ?, ?)",
            [$user['org_id'], $email, 'desk.multiapp.fr', $_SERVER['REMOTE_ADDR'] ?? '']
        );

        return true;
    }

    public static function logout(): void {
        session_destroy();
        header('Location: /login');
        exit;
    }

    public static function check(): bool {
        return isset($_SESSION['user']);
    }

    public static function user(): array {
        return $_SESSION['user'] ?? [];
    }

    public static function orgId(): int {
        return (int)($_SESSION['user']['org_id'] ?? 0);
    }

    public static function userEmail(): string {
        return $_SESSION['user']['email'] ?? '';
    }

    public static function isAdmin(): bool {
        return in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin']);
    }

    public static function isSuperAdmin(): bool {
        return ($_SESSION['user']['role'] ?? '') === 'superadmin';
    }

    public static function require(): void {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function settings(): array {
        return $_SESSION['settings'] ?? [];
    }

    public static function filterColor(): string {
        return $_SESSION['settings']['filter_color'] ?? '#6b6ef9';
    }
}
