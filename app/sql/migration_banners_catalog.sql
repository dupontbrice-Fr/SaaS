-- Migration: Catalogue par bannière
-- À exécuter manuellement sur la base de données

ALTER TABLE `banners`
  ADD COLUMN IF NOT EXISTS `catalog_id` INT DEFAULT NULL AFTER `org_id`,
  ADD KEY IF NOT EXISTS `idx_banner_catalog` (`catalog_id`);
