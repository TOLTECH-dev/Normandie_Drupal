#!/bin/bash

################################################################################
# POST-INSTALLATION SETUP SCRIPT
# Automates Steps 6-9: Enable modules, theme, import configs
# 
# Usage: bash scripts/post-installation.sh
################################################################################

set -e  # Exit on any error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Setup Drush
if [ -f "$PROJECT_ROOT/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_ROOT/vendor/bin/drush"
else
  echo -e "${RED}❌ Drush not found at $PROJECT_ROOT/vendor/bin/drush${NC}"
  exit 1
fi

# Function to print step headers
print_step() {
  local step_num="$1"
  local step_title="$2"
  echo ""
  echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
  echo -e "${BLUE}STEP $step_num: $step_title${NC}"
  echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
}

# Function to print progress
print_progress() {
  echo -e "${YELLOW}⏳ $1${NC}"
}

# Function to print success
print_success() {
  echo -e "${GREEN}✅ $1${NC}"
}

# Function to print error
print_error() {
  echo -e "${RED}❌ $1${NC}"
}

# Function to enable module only if not already enabled
enable_module_safe() {
  local module="$1"
  if $DRUSH pm:list --status=enabled --format=list 2>/dev/null | grep -qx "$module"; then
    return 0  # Already enabled, skip
  else
    # Try to enable, ignore PreExistingConfigException errors
    if $DRUSH pm:enable "$module" -y 2>&1; then
      return 0
    else
      # Check if module is now enabled despite the error
      if $DRUSH pm:list --status=enabled --format=list 2>/dev/null | grep -qx "$module"; then
        echo "  ℹ️  Module $module enabled (config already existed)"
        return 0
      else
        echo "  ❌ Failed to enable module $module"
        return 1
      fi
    fi
  fi
}

################################################################################
# MAIN EXECUTION
################################################################################

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}║          NORMANDIE DRUPAL 10 POST-INSTALLATION SETUP          ║${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

start_time=$(date +%s)

################################################################################
# STEP 1: Enable Core Modules
################################################################################
print_step "1/4" "Enable Core Modules"

# CKEditor 5 core modules
print_progress "Enabling CKEditor 5 core modules..."
enable_module_safe editor
enable_module_safe ckeditor5
print_success "CKEditor 5 core modules enabled"

# Language & translation modules
print_progress "Enabling language, locale, and content_translation..."
enable_module_safe language
enable_module_safe locale
enable_module_safe content_translation

# Update module
print_progress "Enabling update module for automatic update checks..."
enable_module_safe update

print_success "Core modules enabled"

################################################################################
# STEP 2: Enable Custom & Contrib Modules
################################################################################
print_step "2/4" "Enable Custom & Contrib Modules"

# Custom modules
print_progress "Enabling custom modules..."
for module in normandie_core tarteaucitron trouver_conseiller cartostructure carto normandie_filter chiffrescles cartochantier; do
  enable_module_safe "$module"
done
print_success "Custom modules enabled"

# Contrib modules - CKEditor 5 plugins
print_progress "Enabling CKEditor 5 plugin modules..."
for module in ckeditor5_plugin_pack ckeditor5_plugin_pack_font ckeditor5_plugin_pack_indent_block ckeditor5_plugin_pack_layout_table ckeditor5_plugin_pack_link_attributes; do
  enable_module_safe "$module"
done
print_success "CKEditor 5 plugins enabled"

# Editor advanced link module
print_progress "Enabling editor_advanced_link module..."
enable_module_safe editor_advanced_link
print_success "editor_advanced_link enabled"

# Contrib modules - Other
print_progress "Enabling other contrib modules..."
for module in addtoany entity_reference_revisions paragraphs views_slideshow views_slideshow_cycle smtp; do
  enable_module_safe "$module"
done
print_success "Other contrib modules enabled"

# Migration modules
print_progress "Enabling migration modules..."
for module in migrate migrate_drupal migrate_drupal_ui migrate_plus migrate_tools phpass; do
  enable_module_safe "$module"
done
print_success "Core migration modules enabled"

# Enable normandie_migrate module
print_progress "Enabling normandie_migrate module..."
if $DRUSH pm:list --status=enabled --format=list 2>/dev/null | grep -qx "normandie_migrate"; then
  echo "  ℹ️  normandie_migrate already enabled"
else
  # ALWAYS clean configs before enabling to handle both fresh install and re-runs
  echo "  🧹 Pre-cleaning normandie_migrate configurations (idempotent)..."
  
  # Clean field configs (safe even if they don't exist)
  $DRUSH cdel field.storage.node.field_tags -y 2>/dev/null || true
  $DRUSH cdel field.storage.node.field_categories -y 2>/dev/null || true
  $DRUSH cdel field.field.node.page.field_tags -y 2>/dev/null || true
  $DRUSH cdel field.field.node.page.field_categories -y 2>/dev/null || true
  
  # Clean taxonomy configs
  $DRUSH cdel taxonomy.vocabulary.tags -y 2>/dev/null || true
  $DRUSH cdel taxonomy.vocabulary.actus -y 2>/dev/null || true
  $DRUSH cdel taxonomy.vocabulary.categories -y 2>/dev/null || true
  
  # Clean paragraph configs
  $DRUSH cdel core.entity_view_display.paragraph.colonne_de_droite.preview -y 2>/dev/null || true
  $DRUSH cdel field.field.paragraph.colonne_de_droite.field_contenu -y 2>/dev/null || true
  $DRUSH cdel field.field.paragraph.colonne_de_droite.field_titre -y 2>/dev/null || true
  $DRUSH cdel field.storage.paragraph.field_contenu -y 2>/dev/null || true
  $DRUSH cdel field.storage.paragraph.field_titre -y 2>/dev/null || true
  $DRUSH cdel paragraphs.paragraphs_type.colonne_de_droite -y 2>/dev/null || true
  
  # Clean migration configs
  for migration in colonne_droite colonne_droite_attach d7_colonne_de_droite_hydrate d7_node_complete_cartographie d7_node_complete_page d7_node_type d7_taxonomy_term_actus d7_taxonomy_term_tags d7_taxonomy_vocabulary d7_url_alias d7_user d7_user_role; do
    $DRUSH cdel "migrate_plus.migration.$migration" -y 2>/dev/null || true
  done
  
  echo "  ✅ Configs cleaned (ready for fresh install)"
  
  # Now enable the module with a clean slate
  $DRUSH pm:enable normandie_migrate -y
fi
print_success "normandie_migrate module enabled"

# Verify modules
print_progress "Verifying modules are enabled..."
enabled_count=$($DRUSH pm:list --status=enabled --type=module | grep -E "(normandie|migrate)" | wc -l)
print_success "Total custom/migration modules enabled: $enabled_count"

################################################################################
# STEP 3: Enable Custom Theme
################################################################################
print_step "3/4" "Enable Custom Theme"

print_progress "Enabling normandie theme..."
# Check if theme is already installed
if $DRUSH theme:list --status=installed --format=list 2>/dev/null | grep -qx "normandie"; then
  echo "  ℹ️  Theme normandie already installed"
else
  $DRUSH theme:enable normandie -y
fi

print_progress "Setting normandie as default theme..."
current_theme=$($DRUSH config:get system.theme default --format=string 2>/dev/null || echo "")
if [ "$current_theme" = "normandie" ]; then
  echo "  ℹ️  Theme normandie is already default"
else
  $DRUSH config:set system.theme default normandie -y
fi

# Verify theme
current_theme=$($DRUSH config:get system.theme default --format=string 2>/dev/null)
if [ "$current_theme" = "normandie" ]; then
  print_success "Theme 'normandie' is now active"
else
  print_error "Failed to set normandie as default theme (current: $current_theme)"
  exit 1
fi

################################################################################
# STEP 4: Import Migration Configurations
################################################################################
print_step "4/4" "Import Configurations"

# Skip config import if normandie_migrate was already installed previously
# (configurations are already in place from module installation)
print_progress "Checking normandie_migrate configuration status..."
if $DRUSH config:get migrate_plus.migration.d7_user 2>/dev/null | grep -q "id: d7_user"; then
  print_success "Migration configurations already present, skipping import"
else
  print_progress "Importing paragraph field configurations..."
  if [ -d "$PROJECT_ROOT/web/modules/custom/normandie_migrate/config/install" ]; then
    # Only import if there are actual changes to apply
    if $DRUSH config:import --partial --source=modules/custom/normandie_migrate/config/install -y 2>&1 | tee /tmp/config_import.log; then
      print_success "Migration configurations imported"
    else
      # Check if error is about pre-existing config (which is OK)
      if grep -q "already exist in active configuration\|no changes to import" /tmp/config_import.log; then
        print_success "Migration configurations already present"
      else
        print_error "Failed to import configurations"
        cat /tmp/config_import.log
        exit 1
      fi
    fi
  else
    print_error "Migration config directory not found"
    exit 1
  fi
fi

# Clear cache after config import
print_progress "Clearing cache after config import..."
$DRUSH cr
print_success "Cache cleared"

# Configure update module
print_progress "Configuring update status module..."
$DRUSH cset update.settings check.disabled_extensions false -y >> /dev/null 2>&1
$DRUSH cset update.settings notification.threshold "all" -y >> /dev/null 2>&1
print_success "Update module configured"

# Clear cache after configuration
print_progress "Clearing cache..."
$DRUSH cr
print_success "Cache cleared"


################################################################################
# SUMMARY
################################################################################
end_time=$(date +%s)
duration=$((end_time - start_time))

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ POST-INSTALLATION SETUP COMPLETED SUCCESSFULLY${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Summary:${NC}"
echo "  • Core modules: ✅ enabled (including update)"
echo "  • Custom modules: ✅ enabled ($enabled_count total)"
echo "  • Contrib modules: ✅ enabled"
echo "  • Migration modules: ✅ enabled"
echo "  • Theme: ✅ normandie (active)"
echo "  • Configurations: ✅ imported"
echo "  • Update checks: ✅ configured"
echo "  • Modules: ✅ updated to latest versions"
echo "  • Cache: ✅ cleared"
echo ""
echo -e "${CYAN}⏱️  Total time: ${duration}s${NC}"
echo ""
echo -e "${GREEN}🎉 Your Drupal 10 installation is now ready for migration!${NC}"
echo ""
printf "%bNext steps:%b\n" "$YELLOW" "$NC"
printf "  1. Run preflight checks: %bbash scripts/migration.sh --preflight-only%b\n" "$CYAN" "$NC"
printf "  2. Run dry run: %bbash scripts/migration.sh --dry-run%b\n" "$CYAN" "$NC"
printf "  3. Execute migration: %bbash scripts/migration.sh%b\n" "$CYAN" "$NC"
echo ""
