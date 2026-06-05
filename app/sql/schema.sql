-- MultiApp Database Schema
-- PHP 8.4 / MySQL

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Organizations (multi-tenant)
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings per organization
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','user') DEFAULT 'admin',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Banners
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Screensavers
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Licenses
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL UNIQUE,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Screens
CREATE TABLE IF NOT EXISTS `screens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `license_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL UNIQUE,
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
  KEY `org_id` (`org_id`),
  KEY `token` (`token`),
  KEY `license_id` (`license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Screen logs
CREATE TABLE IF NOT EXISTS `screen_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `screen_id` int(11) NOT NULL,
  `level` enum('info','warning','error') DEFAULT 'info',
  `message` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `screen_id` (`screen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Statistics (click tracking)
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `screen_id` (`screen_id`),
  KEY `clicked_at` (`clicked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certificates
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Media Library (Bibliothèques - NEW)
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit logs
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
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login history
CREATE TABLE IF NOT EXISTS `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `logged_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product modification history (for certificates)
CREATE TABLE IF NOT EXISTS `product_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `org_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default organization
INSERT INTO `organizations` (`id`, `name`, `slug`, `parent_id`) VALUES (1, 'MultiApp', 'multiapp', NULL);

-- Default admin user (password: Admin@2024)
INSERT INTO `users` (`org_id`, `email`, `password`, `role`) VALUES
(1, 'admin@multiapp.fr', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');

-- Default settings
INSERT INTO `settings` (`org_id`, `filter_color`, `screensaver_delay`) VALUES (1, '#6b6ef9', 300);
