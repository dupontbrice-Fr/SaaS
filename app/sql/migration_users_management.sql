-- Migration : gestion des utilisateurs et protection du contenu admin
-- À exécuter une fois sur la base de données en production

-- Ajoute un indicateur de protection administrateur sur les fichiers de la bibliothèque.
-- Les fichiers uploadés par un admin ne peuvent pas être supprimés par un utilisateur standard.
ALTER TABLE `media_library`
  ADD COLUMN `admin_protected` TINYINT(1) NOT NULL DEFAULT 0
  AFTER `path`;
