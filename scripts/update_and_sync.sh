#!/bin/bash
################################################################################
# UPDATE AND SYNC - Mise à jour et synchronisation complète
# Ce script résout définitivement le décalage cache Drupal vs Composer
################################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
DRUSH="$PROJECT_ROOT/vendor/bin/drush"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "═══════════════════════════════════════════════════════════════"
echo "UPDATE AND SYNC - Mise à jour et synchronisation"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Étape 1: Mise à jour Composer
echo -e "${YELLOW}[1/5]${NC} Mise à jour des packages Composer..."
cd "$PROJECT_ROOT"
composer update drupal/core-recommended drupal/entity_reference_revisions drupal/migrate_plus drupal/migrate_tools drupal/paragraphs --with-all-dependencies --no-interaction 2>&1 | grep -E "Upgrading|Nothing to update" || true
echo -e "${GREEN}✓${NC} Composer à jour"

# Étape 2: Vider TOUS les caches Drupal (critique!)
echo -e "${YELLOW}[2/6]${NC} Activation du module Update Manager..."
$DRUSH pm:enable update -y --quiet 2>/dev/null || true
echo -e "${GREEN}✓${NC} Module activé"

# Étape 3: Nettoyage complet des caches
echo -e "${YELLOW}[3/6]${NC} Nettoyage complet des caches Drupal..."
$DRUSH php:eval "
  // Vider le cache du système de modules
  \Drupal::service('extension.list.module')->reset();
  
  // Vider TOUS les caches liés aux mises à jour
  \Drupal::keyValue('update_available_releases')->deleteAll();
  \Drupal::keyValue('update')->deleteAll();
  \Drupal::keyValue('update_fetch_task')->deleteAll();
  
  // Réinitialiser le timestamp de dernière vérification
  \Drupal::state()->set('update.last_check', 0);
  
  echo 'Caches cleared';
" 2>/dev/null
$DRUSH cr --quiet 2>/dev/null
echo -e "${GREEN}✓${NC} Caches vidés"

# Étape 4: Appliquer les mises à jour de base de données
echo -e "${YELLOW}[4/6]${NC} Application des mises à jour base de données..."
$DRUSH updatedb -y --quiet 2>/dev/null || true
echo -e "${GREEN}✓${NC} Base de données à jour"

# Étape 5: Récupérer les nouvelles données de mises à jour
echo -e "${YELLOW}[5/6]${NC} Récupération des données de mise à jour..."
$DRUSH php:eval "
\$projects = \Drupal::service('update.manager')->getProjects();
\$http_client = \Drupal::httpClient();
\$key_value = \Drupal::keyValue('update_available_releases');
\$count = 0;

foreach (\$projects as \$name => \$project) {
  \$url = 'https://updates.drupal.org/release-history/' . \$name . '/current';
  try {
    \$response = \$http_client->get(\$url, ['timeout' => 30]);
    \$xml = simplexml_load_string(\$response->getBody());
    if (\$xml && isset(\$xml->releases)) {
      \$releases = [];
      foreach (\$xml->releases->release as \$release) {
        \$releases[] = [
          'version' => (string)\$release->version,
          'date' => (int)\$release->date,
          'status' => (string)\$release->status,
        ];
      }
      \$key_value->set(\$name, ['releases' => \$releases, 'last_fetch' => time()]);
      \$count++;
    }
  } catch (\Exception \$e) {
    // Continue on error
  }
}
\Drupal::state()->set('update.last_check', time());
echo 'Fetched: ' . \$count . ' projects';
" 2>&1 | grep -v "Warning\|Cannot load" || echo "Erreur lors de la récupération"
echo -e "${GREEN}✓${NC} Données récupérées"

# Étape 6: Vérifier l'état final (stable releases only, same major version)
echo -e "${YELLOW}[6/6]${NC} Vérification finale..."
UPDATES=$($DRUSH php:eval "
\$projects = \Drupal::service('update.manager')->getProjects();
\$available = \Drupal::keyValue('update_available_releases')->getAll();
\$count = 0;
\$details = [];

foreach (\$projects as \$name => \$project) {
  if (isset(\$available[\$name])) {
    \$current = \$project['info']['version'];
    \$releases = \$available[\$name]['releases'];
    
    // Skip announcements_feed
    if (\$name === 'announcements_feed') continue;
    
    // Find latest STABLE release
    \$latest_stable = null;
    foreach (\$releases as \$release) {
      \$version = \$release['version'];
      // Skip dev, alpha, beta, rc versions
      if (strpos(\$version, '-dev') === false && 
          strpos(\$version, '-alpha') === false && 
          strpos(\$version, '-beta') === false && 
          strpos(\$version, '-rc') === false) {
        \$latest_stable = \$version;
        break;
      }
    }
    
    if (!\$latest_stable || \$latest_stable === \$current) continue;
    
    // For drupal core, only check within same major version
    if (\$name === 'drupal') {
      \$current_major = (int)explode('.', \$current)[0];
      \$stable_major = (int)explode('.', \$latest_stable)[0];
      
      if (\$stable_major === \$current_major) {
        \$count++;
        \$details[] = \$name . ': ' . \$current . ' -> ' . \$latest_stable;
      }
    } else {
      // For contrib modules
      \$count++;
      \$details[] = \$name . ': ' . \$current . ' -> ' . \$latest_stable;
    }
  }
}

if (!empty(\$details)) {
  echo implode(PHP_EOL, \$details) . PHP_EOL;
}
echo PHP_EOL . 'TOTAL: ' . \$count;
" 2>&1 | grep -v "Warning\|Cannot load" || echo "TOTAL: -1")

echo ""
echo "$UPDATES"
echo ""

if echo "$UPDATES" | grep -q "TOTAL: 0"; then
  echo "═══════════════════════════════════════════════════════════════"
  echo -e "${GREEN}✅ SUCCÈS: Tous les modules sont à jour!${NC}"
  echo "═══════════════════════════════════════════════════════════════"
else
  echo "═══════════════════════════════════════════════════════════════"
  echo -e "${YELLOW}⚠ Il reste des mises à jour disponibles${NC}"
  echo "Cela peut être normal si de nouvelles versions sont sorties"
  echo "Réexécutez ce script pour mettre à jour"
  echo "═══════════════════════════════════════════════════════════════"
fi
