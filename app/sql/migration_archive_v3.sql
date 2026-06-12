-- ============================================================
-- Migration : archivage étendu — banners, screensavers, produits fantômes
-- À exécuter manuellement dans phpMyAdmin une seule fois.
-- ============================================================

-- 1. Colonne archived_at sur banners
ALTER TABLE `banners`
  ADD COLUMN IF NOT EXISTS `archived_at` DATETIME DEFAULT NULL AFTER `created_at`,
  ADD KEY IF NOT EXISTS `idx_banner_archived` (`archived_at`);

-- 2. Colonne archived_at sur screensavers
ALTER TABLE `screensavers`
  ADD COLUMN IF NOT EXISTS `archived_at` DATETIME DEFAULT NULL AFTER `created_at`,
  ADD KEY IF NOT EXISTS `idx_screensaver_archived` (`archived_at`);

-- 3. Archivage des produits fantômes :
--    type = media, sans fichier, sans thumbnail, sans URL → invisible côté app mais présent en DB.
UPDATE `products`
SET    `archived_at` = NOW()
WHERE  `archived_at` IS NULL
  AND  `file_type`   = 'media'
  AND  (`file_path` IS NULL OR `file_path` = '')
  AND  (`thumbnail`  IS NULL OR `thumbnail`  = '')
  AND  (`url`        IS NULL OR `url`        = '');

-- 4. (Optionnel) Archiver un produit précis par nom — décommenter et adapter :
-- UPDATE `products` SET `archived_at` = NOW() WHERE `name` = 'test' AND `archived_at` IS NULL;

-- 5. (Optionnel) Archiver les produits inactifs non encore archivés :
-- UPDATE `products` SET `archived_at` = NOW() WHERE `status` = 'inactive' AND `archived_at` IS NULL;
