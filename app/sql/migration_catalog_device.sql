-- Migration: Multi-catalog + device assignment
-- Run once on the existing database

-- 1. Catalogs table
CREATE TABLE IF NOT EXISTS `catalogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Link categories to a catalog (nullable = belongs to all / default)
ALTER TABLE `categories`
  ADD COLUMN IF NOT EXISTS `catalog_id` int(11) DEFAULT NULL AFTER `org_id`,
  ADD KEY IF NOT EXISTS `catalog_id` (`catalog_id`);

-- 3. Link screens to a catalog (which content this screen shows)
ALTER TABLE `screens`
  ADD COLUMN IF NOT EXISTS `catalog_id` int(11) DEFAULT NULL AFTER `org_id`,
  ADD KEY IF NOT EXISTS `catalog_id` (`catalog_id`);
