<?php
/**
 * MultiApp - Script de déploiement automatique
 *
 * INSTRUCTIONS :
 * 1. Uploadez ce fichier SEUL à la racine de votre hébergement via FTP
 * 2. Accédez-y via navigateur : http://saasapp.s196298.fvl-001.webo-facto.com/deploy.php
 * 3. Le script crée la base de données et configure les dossiers
 * 4. SUPPRIMEZ ce fichier après installation !
 */

// ── CONFIGURATION ─────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbsaasapps196298com');
define('DB_USER', 'saasaps196298com');
define('DB_PASS', 'mcYDdXEdYUHnKwk8Eq');

$step = $_GET['step'] ?? 'check';
$errors = [];
$success = [];

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>MultiApp - Installation</title>
<style>
body { font-family:Arial,sans-serif; background:#12132a; color:#fff; padding:40px; max-width:800px; margin:0 auto; }
h1 { color:#6b6ef9; margin-bottom:8px; }
h2 { font-size:16px; color:#a0a3c4; margin-top:24px; margin-bottom:12px; }
.card { background:#1e2040; border:1px solid #2e3060; border-radius:12px; padding:20px; margin-bottom:16px; }
.ok { color:#22c55e; } .err { color:#ef4444; } .warn { color:#f59e0b; }
.btn { display:inline-block; background:#6b6ef9; color:white; padding:12px 28px; border-radius:8px; text-decoration:none; font-weight:700; margin-top:16px; border:none; cursor:pointer; font-size:14px; }
.btn:hover { background:#5558e8; }
.btn-danger { background:#ef4444; }
pre { background:#0a0a1a; padding:12px; border-radius:6px; font-size:12px; overflow-x:auto; color:#a0f0a0; }
</style>
</head>
<body>

<h1>🚀 MultiApp — Installation</h1>
<p style="color:#a0a3c4; margin-bottom:24px;">Assistant de déploiement automatique</p>

<?php

// ── STEP: CHECK ───────────────────────────────────────────────────
if ($step === 'check') {
    echo '<div class="card">';
    echo '<h2>📋 Vérifications préalables</h2>';

    // PHP version
    $phpOk = version_compare(PHP_VERSION, '8.0', '>=');
    echo '<p>' . ($phpOk ? '✅' : '❌') . ' PHP ' . PHP_VERSION . ($phpOk ? ' (OK)' : ' (requis: 8.0+)') . '</p>';

    // PDO MySQL
    $pdoOk = extension_loaded('pdo_mysql');
    echo '<p>' . ($pdoOk ? '✅' : '❌') . ' Extension PDO MySQL ' . ($pdoOk ? '(OK)' : '(manquante)') . '</p>';

    // GD
    $gdOk = extension_loaded('gd');
    echo '<p>' . ($gdOk ? '✅' : '⚠️') . ' Extension GD ' . ($gdOk ? '(OK)' : '(optionnelle)') . '</p>';

    // Fileinfo
    $fiOk = extension_loaded('fileinfo');
    echo '<p>' . ($fiOk ? '✅' : '❌') . ' Extension Fileinfo ' . ($fiOk ? '(OK)' : '(requise pour uploads)') . '</p>';

    // DB connection test
    try {
        $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '<p>✅ Connexion base de données (OK)</p>';
        $dbOk = true;
    } catch (Exception $e) {
        echo '<p>❌ Base de données : ' . htmlspecialchars($e->getMessage()) . '</p>';
        $dbOk = false;
    }

    // Write permissions
    $uploadDir = __DIR__ . '/public/uploads/';
    $canWrite = is_writable(__DIR__) || (is_dir($uploadDir) && is_writable($uploadDir));
    echo '<p>' . ($canWrite ? '✅' : '⚠️') . ' Écriture sur le disque ' . ($canWrite ? '(OK)' : '(vérifiez les droits sur public/uploads/)') . '</p>';

    echo '</div>';

    if ($dbOk) {
        echo '<a href="?step=install" class="btn">▶ Lancer l\'installation</a>';
    } else {
        echo '<p class="err">Corrigez les erreurs avant de continuer.</p>';
    }
}

// ── STEP: INSTALL ─────────────────────────────────────────────────
if ($step === 'install') {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo '<div class="card">';
    echo '<h2>🗄️ Création des tables</h2>';

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `filter_color` varchar(20) DEFAULT '#6b6ef9',
  `screensaver_delay` int(11) DEFAULT 300,
  `custom_message` text DEFAULT NULL,
  `widget_qr` tinyint(1) DEFAULT 1,
  `widget_pmr` tinyint(1) DEFAULT 0,
  `widget_weather` tinyint(1) DEFAULT 0,
  `widget_datetime` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','user') DEFAULT 'admin',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `show_title` tinyint(1) DEFAULT 1,
  `viewable_external` tinyint(1) DEFAULT 1,
  `position` int(11) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `file_type` enum('media','url') DEFAULT 'media',
  `file_path` varchar(500) DEFAULT NULL,
  `file_mime` varchar(100) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `downloadable` tinyint(1) DEFAULT 0,
  `show_title` tinyint(1) DEFAULT 1,
  `viewable_external` tinyint(1) DEFAULT 1,
  `enable_cache` tinyint(1) DEFAULT 0,
  `protected_nav` tinyint(1) DEFAULT 0,
  `position` int(11) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_type` enum('media','url') DEFAULT 'media',
  `file_path` varchar(500) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `program_start` datetime DEFAULT NULL,
  `program_end` datetime DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `screensavers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_type` enum('media','url') DEFAULT 'media',
  `file_path` varchar(500) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `position` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `screens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `license_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` enum('online','offline') DEFAULT 'offline',
  `resolution` varchar(50) DEFAULT NULL,
  `orientation` enum('landscape','portrait') DEFAULT 'landscape',
  `os` varchar(100) DEFAULT NULL,
  `software_version` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `android_id` varchar(100) DEFAULT NULL,
  `firmware` varchar(100) DEFAULT NULL,
  `storage_used_screen` bigint DEFAULT 0,
  `storage_used_license` bigint DEFAULT 0,
  `storage_available` bigint DEFAULT 0,
  `preview_image` varchar(500) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  `last_launch` datetime DEFAULT NULL,
  `is_demo` tinyint(1) DEFAULT 0,
  `demo_expires_at` datetime DEFAULT NULL,
  `demo_orientation` enum('landscape','portrait') DEFAULT 'landscape',
  `catalog_type` varchar(50) DEFAULT 'catalog',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `screen_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `screen_id` int(11) NOT NULL,
  `level` enum('info','warning','error') DEFAULT 'info',
  `message` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `screen_id` int(11) DEFAULT NULL,
  `license_code` varchar(20) DEFAULT NULL,
  `item_type` enum('category','product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `is_external` tinyint(1) DEFAULT 0,
  `clicked_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `media_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint DEFAULT 0,
  `path` varchar(500) NOT NULL,
  `used_in` varchar(50) DEFAULT NULL,
  `used_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `org_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

    $tables = explode("ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", $sql);
    $created = 0;
    foreach ($tables as $t) {
        $t = trim($t);
        if (empty($t)) continue;
        try {
            $pdo->exec($t . " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $created++;
        } catch (Exception $e) {
            echo '<p class="err">Erreur table : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
    echo '<p class="ok">✅ ' . $created . ' tables créées / vérifiées</p>';

    // Insert default data
    $pdo->exec("INSERT IGNORE INTO organizations (id, name, slug) VALUES (1, 'MultiApp', 'multiapp')");
    echo '<p class="ok">✅ Organisation par défaut créée</p>';

    // Admin user: password = Admin@2024
    $hash = password_hash('Admin@2024', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (org_id, email, password, role) VALUES (1, 'admin@multiapp.fr', '{$hash}', 'superadmin')");
    echo '<p class="ok">✅ Utilisateur admin créé (admin@multiapp.fr / Admin@2024)</p>';

    $pdo->exec("INSERT IGNORE INTO settings (org_id, filter_color, screensaver_delay) VALUES (1, '#6b6ef9', 300)");
    echo '<p class="ok">✅ Paramètres initialisés</p>';
    echo '</div>';

    // Create upload directories
    echo '<div class="card">';
    echo '<h2>📁 Création des dossiers uploads</h2>';
    $dirs = [
        'public/uploads/',
        'public/uploads/categories/',
        'public/uploads/products/',
        'public/uploads/products/thumbnails/',
        'public/uploads/banners/',
        'public/uploads/screensavers/',
        'public/uploads/library/',
        'public/uploads/logos/',
    ];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            echo '<p class="ok">✅ Créé: ' . $dir . '</p>';
        } else {
            echo '<p style="color:#a0a3c4;">⊙ Existant: ' . $dir . '</p>';
        }
        // Add .htaccess to prevent PHP execution in uploads
        $htaccess = $path . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -ExecCGI\nAddHandler cgi-script .php .pl .py .jsp .asp .htm .shtml .sh .cgi\n");
        }
    }
    echo '</div>';

    // Rewrite config check
    echo '<div class="card">';
    echo '<h2>⚙️ Configuration PHP</h2>';
    echo '<p>Version PHP : <strong>' . PHP_VERSION . '</strong></p>';
    echo '<p>upload_max_filesize : <strong>' . ini_get('upload_max_filesize') . '</strong></p>';
    echo '<p>post_max_size : <strong>' . ini_get('post_max_size') . '</strong></p>';
    echo '<p>memory_limit : <strong>' . ini_get('memory_limit') . '</strong></p>';
    echo '</div>';

    echo '<div class="card" style="border-color:#22c55e;">';
    echo '<h2 class="ok">🎉 Installation terminée !</h2>';
    echo '<p style="margin-bottom:16px;">Votre plateforme MultiApp est prête.</p>';
    echo '<p><strong>URL :</strong> <a href="http://saasapp.s196298.fvl-001.webo-facto.com/" style="color:#6b6ef9;">http://saasapp.s196298.fvl-001.webo-facto.com/</a></p>';
    echo '<p><strong>Login :</strong> admin@multiapp.fr</p>';
    echo '<p><strong>Mot de passe :</strong> Admin@2024</p>';
    echo '<br>';
    echo '<p class="err"><strong>⚠️ IMPORTANT : Supprimez ce fichier deploy.php du serveur immédiatement après !</strong></p>';
    echo '</div>';
}
?>

</body>
</html>
