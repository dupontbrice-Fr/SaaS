-- ============================================================
-- Migration : unification de la numérotation position
--             (categories + products dans la même grille)
-- Nécessite MySQL 8.0+ (ROW_NUMBER / window functions)
-- À exécuter manuellement dans phpMyAdmin une seule fois.
-- ============================================================
-- Stratégie : catégories → positions impaires (1, 3, 5…)
--             produits    → positions paires  (2, 4, 6…)
-- Résultat  : les deux types s'interfollient naturellement
--             dans la grille selon leur position relative.
--             L'admin peut ensuite réorganiser librement
--             par glisser-déposer pour tout interleaver.
-- ============================================================

-- Étape 1 : catégories — renumérotation par contexte parent
UPDATE categories c
JOIN (
  SELECT id,
    ROW_NUMBER() OVER (
      PARTITION BY org_id, COALESCE(parent_id, 0)
      ORDER BY position, id
    ) * 2 - 1 AS new_pos
  FROM categories
  WHERE archived_at IS NULL
) r ON c.id = r.id
SET c.position = r.new_pos;

-- Étape 2 : produits — renumérotation par contexte catégorie parente
UPDATE products p
JOIN (
  SELECT id,
    ROW_NUMBER() OVER (
      PARTITION BY org_id, COALESCE(category_id, 0)
      ORDER BY position, id
    ) * 2 AS new_pos
  FROM products
  WHERE archived_at IS NULL
) r ON p.id = r.id
SET p.position = r.new_pos;

-- Vérification : aperçu des 20 premiers éléments d'une catégorie
-- (remplacer <CATEGORY_ID> par un id réel pour tester)
-- SELECT 'category' AS type, id, name, position FROM categories WHERE parent_id = <CATEGORY_ID>
-- UNION ALL
-- SELECT 'product',           id, name, position FROM products   WHERE category_id = <CATEGORY_ID>
-- ORDER BY position;
