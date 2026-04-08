-- ============================================================================
-- SCRIPT DE REMPLACEMENT DES URLs POUR L'ENVIRONNEMENT DE RECETTE
-- Base : normandie_drupal_d10_rec (sur 46.18.195.54)
-- ============================================================================
--
-- Remplacements effectues :
--   PROD cheque-eco-energie.normandie.fr
--     -> REC  cheque-eco-energie.recette.normandie.fr
--
--   PROD cheque-eco-energie-normandie-beneficiaire.up-gestion.com
--     -> REC  cheque-eco-energie-normandie-beneficiaire.recette.normandie.fr
--
--   PROD cheque-eco-energie-normandie-partenaire.up-gestion.com
--     -> REC  cheque-eco-energie-normandie-partenaire.recette.normandie.fr
--
--   PROD cheque-eco-energie-normandie.up-gestion.com
--     -> REC  cheque-eco-energie-normandie.recette.normandie.fr
--
-- ============================================================================

-- Desactiver temporairement les checks pour performance
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. CONTENU DES NODES (body) - 14 lignes
-- ============================================================================
UPDATE node__body
   SET body_value = REPLACE(body_value,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE body_value LIKE '%cheque-eco-energie.normandie.fr%';

-- Revisions des nodes - 23 lignes
UPDATE node_revision__body
   SET body_value = REPLACE(body_value,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE body_value LIKE '%cheque-eco-energie.normandie.fr%';

-- ============================================================================
-- 2. PARAGRAPHES (field_contenu) - 2 lignes
-- ============================================================================
UPDATE paragraph__field_contenu
   SET field_contenu_value = REPLACE(field_contenu_value,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE field_contenu_value LIKE '%cheque-eco-energie.normandie.fr%';

-- Revisions des paragraphes - 2 lignes
UPDATE paragraph_revision__field_contenu
   SET field_contenu_value = REPLACE(field_contenu_value,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE field_contenu_value LIKE '%cheque-eco-energie.normandie.fr%';

-- ============================================================================
-- 3. LIENS DE MENU - Site Drupal principal (1 lien)
-- ============================================================================
UPDATE menu_link_content_data
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie.normandie.fr%';

UPDATE menu_link_content_field_revision
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie.normandie.fr%';

-- ============================================================================
-- 4. LIENS DE MENU - Plateforme beneficiaire (2 liens)
-- ============================================================================
UPDATE menu_link_content_data
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie-beneficiaire.up-gestion.com',
       'cheque-eco-energie-normandie-beneficiaire.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie-beneficiaire.up-gestion.com%';

UPDATE menu_link_content_field_revision
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie-beneficiaire.up-gestion.com',
       'cheque-eco-energie-normandie-beneficiaire.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie-beneficiaire.up-gestion.com%';

-- ============================================================================
-- 5. LIENS DE MENU - Plateforme partenaire (2 liens)
-- ============================================================================
UPDATE menu_link_content_data
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie-partenaire.up-gestion.com',
       'cheque-eco-energie-normandie-partenaire.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie-partenaire.up-gestion.com%';

UPDATE menu_link_content_field_revision
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie-partenaire.up-gestion.com',
       'cheque-eco-energie-normandie-partenaire.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie-partenaire.up-gestion.com%';

-- ============================================================================
-- 6. LIENS DE MENU - Mentions legales (1 lien)
-- ============================================================================
UPDATE menu_link_content_data
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie.up-gestion.com',
       'cheque-eco-energie-normandie.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie.up-gestion.com%';

UPDATE menu_link_content_field_revision
   SET link__uri = REPLACE(link__uri,
       'cheque-eco-energie-normandie.up-gestion.com',
       'cheque-eco-energie-normandie.recette.normandie.fr')
 WHERE link__uri LIKE '%cheque-eco-energie-normandie.up-gestion.com%';

-- ============================================================================
-- 7. CONFIGURATION DRUPAL (addtoany - boutons partage social)
-- ============================================================================
UPDATE config
   SET data = REPLACE(data,
       'cheque-eco-energie.normandie.fr',
       'cheque-eco-energie.recette.normandie.fr')
 WHERE name = 'addtoany.settings'
   AND data LIKE '%cheque-eco-energie.normandie.fr%';

-- ============================================================================
-- 8. VIDER LES CACHES apres modification
-- ============================================================================
-- Les tables de cache seront videes par drush cr apres execution
TRUNCATE TABLE cache_config;
TRUNCATE TABLE cache_data;
TRUNCATE TABLE cache_default;
TRUNCATE TABLE cache_discovery;
TRUNCATE TABLE cache_entity;
TRUNCATE TABLE cache_menu;
TRUNCATE TABLE cache_page;
TRUNCATE TABLE cache_render;

-- Restaurer les checks
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- ============================================================================
-- VERIFICATION : compter les URLs restantes (devrait retourner 0 partout)
-- ============================================================================
SELECT 'VERIFICATION - URLs restantes (doit etre 0)' AS info;

SELECT 'node__body' AS tbl, COUNT(*) AS remaining
  FROM node__body WHERE body_value LIKE '%cheque-eco-energie.normandie.fr%'
   AND body_value NOT LIKE '%cheque-eco-energie.recette.normandie.fr%'
UNION ALL
SELECT 'paragraph__field_contenu', COUNT(*)
  FROM paragraph__field_contenu WHERE field_contenu_value LIKE '%cheque-eco-energie.normandie.fr%'
   AND field_contenu_value NOT LIKE '%cheque-eco-energie.recette.normandie.fr%'
UNION ALL
SELECT 'menu_link_content_data', COUNT(*)
  FROM menu_link_content_data WHERE link__uri LIKE '%up-gestion.com%'
UNION ALL
SELECT 'config', COUNT(*)
  FROM config WHERE data LIKE '%cheque-eco-energie.normandie.fr%'
   AND data NOT LIKE '%cheque-eco-energie.recette.normandie.fr%';
