-- ============================================================
-- Migration : propagation du catalog_id aux sous-catégories
-- À exécuter manuellement dans phpMyAdmin une seule fois.
-- Corrige les sous-catégories existantes dont catalog_id = NULL
-- alors que leur parent (direct ou indirect) appartient à un catalogue.
-- ============================================================

-- Chaque passe propage d'un niveau supplémentaire.
-- 5 passes couvrent les hiérarchies jusqu'à 5 niveaux de profondeur.

-- Passe 1 (enfants directs des racines de catalogue)
UPDATE categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
SET c.catalog_id = p.catalog_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND p.archived_at IS NULL;

-- Passe 2
UPDATE categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
SET c.catalog_id = p.catalog_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND p.archived_at IS NULL;

-- Passe 3
UPDATE categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
SET c.catalog_id = p.catalog_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND p.archived_at IS NULL;

-- Passe 4
UPDATE categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
SET c.catalog_id = p.catalog_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND p.archived_at IS NULL;

-- Passe 5
UPDATE categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
SET c.catalog_id = p.catalog_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND p.archived_at IS NULL;

-- Vérification : sous-catégories encore sans catalog_id malgré un parent avec catalog_id
-- (résultat attendu : 0 ligne)
SELECT c.id, c.name, c.parent_id, c.catalog_id
FROM categories c
INNER JOIN categories p ON c.parent_id = p.id AND c.org_id = p.org_id
WHERE c.catalog_id IS NULL
  AND p.catalog_id IS NOT NULL
  AND c.archived_at IS NULL;
