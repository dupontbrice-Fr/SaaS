<?php
// MultiApp Configuration

define('DB_HOST', 'localhost');
define('DB_NAME', 'dbsaasapps196298com');
define('DB_USER', 'saasaps196298com');
define('DB_PASS', 'mcYDdXEdYUHnKwk8Eq');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'MultiApp');
define('APP_URL', 'http://saasapp.s196298.fvl-001.webo-facto.com');
define('APP_VERSION', '1.0.0');

define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', APP_URL . '/public/uploads/');

define('MAX_IMAGE_SIZE', 20 * 1024 * 1024);  // 20 MB
define('MAX_VIDEO_SIZE', 1024 * 1024 * 1024); // 1 GB
define('MAX_PDF_SIZE', 50 * 1024 * 1024);     // 50 MB

define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4']);
define('ALLOWED_PDF_TYPES', ['application/pdf']);

define('SESSION_NAME', 'multiapp_session');
define('CSRF_TOKEN_NAME', '_csrf');

define('TIMEZONE', 'Europe/Paris');
date_default_timezone_set(TIMEZONE);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
