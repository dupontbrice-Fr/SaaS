-- Migration: Écrans et Licences v2
-- Restructuration des licences : pause, catalogue par licence, archivage soft-delete
-- À exécuter manuellement sur la base de données

-- ── 1. Statut "En pause" sur les licences ─────────────────────────────────
ALTER TABLE `licenses`
  ADD COLUMN IF NOT EXISTS `paused` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`;

-- ── 2. Catalogue au niveau de la licence (remplace l'attribution par écran) ─
ALTER TABLE `licenses`
  ADD COLUMN IF NOT EXISTS `catalog_id` INT DEFAULT NULL AFTER `org_id`,
  ADD KEY IF NOT EXISTS `idx_license_catalog` (`catalog_id`);

-- Migrer les catalog_id existants des écrans vers leur licence
-- (uniquement si la licence n'a pas déjà un catalog_id assigné)
UPDATE `licenses` l
INNER JOIN `screens` s ON s.license_id = l.id AND s.catalog_id IS NOT NULL
SET l.catalog_id = s.catalog_id
WHERE l.catalog_id IS NULL;

-- ── 3. Soft-delete sur les licences ──────────────────────────────────────────
ALTER TABLE `licenses`
  ADD COLUMN IF NOT EXISTS `archived_at` DATETIME DEFAULT NULL AFTER `created_at`,
  ADD KEY IF NOT EXISTS `idx_license_archived` (`archived_at`);

-- ── 4. Soft-delete sur les écrans ─────────────────────────────────────────────
ALTER TABLE `screens`
  ADD COLUMN IF NOT EXISTS `archived_at` DATETIME DEFAULT NULL AFTER `updated_at`,
  ADD KEY IF NOT EXISTS `idx_screen_archived` (`archived_at`);

-- ── NOTE : Suppression définitive des archives de plus de 30 jours ───────────
-- Pas de cron disponible : exécuter manuellement cette requête chaque mois
-- (ou planifier via un outil externe : cron serveur, planificateur Windows, etc.)
--
-- DELETE FROM screens WHERE archived_at IS NOT NULL AND archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
-- DELETE FROM licenses WHERE archived_at IS NOT NULL AND archived_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
