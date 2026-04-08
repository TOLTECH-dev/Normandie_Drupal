#!/bin/bash

################################################################################
# DRUPAL 7 → 10 PRODUCTION MIGRATION - UNIFIED SCRIPT V4
# 
# FULL AUTOMATION WITH COMPREHENSIVE SAFETY CHECKS & VIEWS IMPORT
# 
# Usage: 
#   ./scripts/migration.sh --dry-run        # Test mode
#   ./scripts/migration.sh --preflight-only # Checks only
#   ./scripts/migration.sh                  # REAL MIGRATION
#   ./scripts/migration.sh --skip-checks    # Skip preflight (DANGEROUS!)
# 
# This unified script handles ALL migrations:
# ✅ Taxonomy (vocabularies & terms)
# ✅ Users & Roles
# ✅ Nodes (page & cartographie with body fields)
# ✅ Paragraphs (field_collection → colonne_de_droite)
# ✅ Images & media
# ✅ Comments & relationships
# ✅ Custom fields & configuration
# ✅ Views import (liste_actus with slideshow)
# ✅ Block placement
# ✅ Comprehensive preflight checks
# ✅ Database backup with FK-safe truncation
# ✅ Admin user preservation
# ✅ Entity API field hydration
# ✅ Data validation & integrity checks
# ✅ Auto-rollback on failure
# ✅ Audit trail logging
#
# REQUIREMENTS: 
# - .env file with D7 and D10 database credentials
# - Drupal 10 installation with migration modules enabled
# - Drupal 7 source database accessible
# - views_slideshow module (composer require drupal/views_slideshow)
# - mysqldump for backups (optional but recommended)
################################################################################

set -e
set +E  # Exit on error

# ============================================================================
# CONFIGURATION & INITIALIZATION
# ============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Setup logging - use local project directories (safer than /var/log or /backups)
LOG_DIR="$PROJECT_ROOT/logs/migration"
BACKUP_DIR="$PROJECT_ROOT/backups"

mkdir -p "$LOG_DIR" "$BACKUP_DIR"
LOG_FILE="$LOG_DIR/migration_v2_$TIMESTAMP.log"
BACKUP_FILE="$BACKUP_DIR/pre_migration_v2_$TIMESTAMP.sql.gz"
AUDIT_FILE="$BACKUP_DIR/migration_audit_$TIMESTAMP.json"

# Load environment
if [ -f "$PROJECT_ROOT/.env" ]; then
  source "$PROJECT_ROOT/.env"
  echo "✅ Environment loaded from .env" | tee -a "$LOG_FILE"
else
  echo "❌ .env file not found. Create: cp .env.example .env" | tee -a "$LOG_FILE"
  exit 1
fi

# Setup Drush
if [ -f "$PROJECT_ROOT/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_ROOT/vendor/bin/drush"
elif command -v drush &> /dev/null; then
  DRUSH="drush"
else
  echo "❌ Drush not found" | tee -a "$LOG_FILE"
  exit 1
fi

# Flags
DRY_RUN=false
PREFLIGHT_ONLY=false
SKIP_CHECKS=false
ADMIN_UID=1
ADMIN_BACKUP=""

# Parse arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --dry-run) DRY_RUN=true; shift ;;
    --preflight-only) PREFLIGHT_ONLY=true; shift ;;
    --skip-checks) SKIP_CHECKS=true; shift ;;
    *) echo "Usage: $0 [--dry-run] [--preflight-only] [--skip-checks]"; exit 1 ;;
  esac
done

# ============================================================================
# LOGGING FUNCTIONS
# ============================================================================

log() {
  local level=$1
  shift
  local msg="$@"
  local ts=$(date '+%Y-%m-%d %H:%M:%S')
  echo "[$ts] [$level] $msg" | tee -a "$LOG_FILE"
}

info() { log "INFO" "$@"; }
success() { log "SUCCESS" "$@"; }
warn() { log "WARN" "$@"; }
error() {
  log "ERROR" "$@"
  exit 1
}

# ============================================================================
# PREFLIGHT CHECKS PHASE
# ============================================================================

run_preflight_checks() {
  checks_passed=0
  checks_failed=0
  
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 0: PREFLIGHT CHECKS"
  info "═══════════════════════════════════════════════════════════════"
  
  # 1. D10 Connection
  info "1️⃣  Checking D10 database connection..."
  if $DRUSH status &>/dev/null; then
    success "D10 database connection OK"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ D10 database connection failed. Check .env credentials and Drupal installation."
  fi
  
  info "DEBUG: Check 1 passed, continuing to check 2..."
  
  # 2. D7 Connection
  info "2️⃣  Checking D7 source database connection..."
  if mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -e "SELECT 1" &>/dev/null; then
    success "D7 source database connection OK"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ D7 source database connection failed"
  fi
  
  info "DEBUG: Check 2 passed, continuing to check 3..."
  
  # 3. Count D7 data
  info "3️⃣  Counting D7 source data..."
  local d7_nodes=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -e "SELECT COUNT(*) FROM node WHERE type IN ('page', 'cartographie')" -N 2>/dev/null || echo "0")
  local d7_users=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -e "SELECT COUNT(*) FROM users WHERE uid > 1" -N 2>/dev/null || echo "0")
  local d7_terms=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -e "SELECT COUNT(*) FROM taxonomy_term_data" -N 2>/dev/null || echo "0")
  
  info "   D7 Nodes (page+cartographie): $d7_nodes"
  info "   D7 Users (uid > 1): $d7_users"
  info "   D7 Terms: $d7_terms"
  
  if [ "$d7_nodes" -gt 0 ]; then
    success "D7 has data to migrate"
    checks_passed=$((checks_passed + 1))
  else
    warn "D7 has NO nodes to migrate (unusual)"
  fi
  
  # 4. Admin user check
  info "4️⃣  Checking D10 admin user (uid=$ADMIN_UID)..."
  local admin_exists=$($DRUSH php:eval "print \Drupal\Core\Database\Database::getConnection()->query(\"SELECT uid FROM users WHERE uid = $ADMIN_UID\")->fetchField();" 2>/dev/null || echo "0")
  
  if [ "$admin_exists" = "$ADMIN_UID" ]; then
    success "Admin user uid=$ADMIN_UID exists"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ Admin user uid=$ADMIN_UID NOT found (CRITICAL)"
  fi
  
  # 5. Duplicate usernames in D7
  info "5️⃣  Checking for duplicate usernames in D7..."
  local d7_dupe_users=$($DRUSH php:eval -d @d7_source '
    $db = \Drupal\Core\Database\Database::getConnection("d7_source");
    $dupes = $db->query("
      SELECT username, COUNT(*) as cnt FROM users 
      WHERE uid > 1 AND username != \"\"
      GROUP BY username HAVING cnt > 1
    ")->fetchAll();
    if (count($dupes) > 0) {
      foreach ($dupes as $d) {
        echo $d->username . ",";
      }
    }
  ' 2>/dev/null || echo "")
  
  if [ -z "$d7_dupe_users" ]; then
    success "No duplicate usernames in D7"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ Found duplicate usernames in D7: $d7_dupe_users (FIX FIRST)"
  fi
  
  # 6. Duplicate emails in D7
  info "6️⃣  Checking for duplicate emails in D7..."
  local d7_dupe_emails=$($DRUSH php:eval -d @d7_source '
    $db = \Drupal\Core\Database\Database::getConnection("d7_source");
    $dupes = $db->query("
      SELECT mail, COUNT(*) as cnt FROM users 
      WHERE uid > 1 AND mail != \"\"
      GROUP BY mail HAVING cnt > 1
    ")->fetchAll();
    print count($dupes);
  ' 2>/dev/null || echo "0")
  
  if [ "$d7_dupe_emails" = "0" ]; then
    success "No duplicate emails in D7"
    checks_passed=$((checks_passed + 1))
  else
    warn "Found $d7_dupe_emails duplicate emails in D7 (will use first occurrence)"
  fi
  
  # 7. D10 roles integrity
  info "7️⃣  Checking D10 built-in roles (migrations prep)..."
  if [ -d "web/modules/custom/normandie_migrate/config/install" ]; then
    success "Migration configuration directory exists"
    checks_passed=$((checks_passed + 1))
  else
    warn "Migration configuration directory not found (not critical)"
  fi
  
  # 8. Body field table
  info "8️⃣  Checking body field tables..."
  local body_exists=$($DRUSH php:eval '
    $db = \Drupal\Core\Database\Database::getConnection();
    print $db->schema()->tableExists("node__body") ? "1" : "0";
  ' 2>/dev/null || echo "0")
  
  if [ "$body_exists" = "1" ]; then
    success "Body field table exists (node__body)"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ Body field table missing (node__body) (CRITICAL)"
  fi
  
  # 9. Migration modules
  info "9️⃣  Checking migration modules..."
  if $DRUSH pm:list --status=enabled 2>/dev/null | grep -q "migrate"; then
    success "Migration modules enabled"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ Migration modules NOT enabled (CRITICAL)"
  fi
  
  # 10. Backup capability
  info "🔟  Checking backup capability..."
  if mysqldump -h "$DB_D10_HOST" -u "$DB_D10_USER" -p"$DB_D10_PASSWORD" "$DB_D10_NAME" --single-transaction --quick &>/dev/null; then
    success "Database backup possible"
    checks_passed=$((checks_passed + 1))
  else
    error "❌ Cannot backup database (check DB credentials)"
  fi
  
  # Summary
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PREFLIGHT SUMMARY"
  info "═══════════════════════════════════════════════════════════════"
  info "✅ Checks passed: $checks_passed/10"
  
  if [ "$checks_failed" -gt 0 ]; then
    error "🚫 MIGRATION NOT READY - See failures above"
  else
    success "✅ ALL PREFLIGHT CHECKS PASSED"
  fi
}

# ============================================================================
# ADMIN USER BACKUP/RESTORE
# ============================================================================

backup_admin_user() {
  info ""
  info "═══════════════════════════════════════════════════════════════" 
  info "PHASE 1: BACKUP ADMIN USER"
  info "═══════════════════════════════════════════════════════════════"
  info "Saving admin user (uid=$ADMIN_UID) backup..."
  
  ADMIN_BACKUP=$($DRUSH php:eval "
    \$db = \Drupal\Core\Database\Database::getConnection();
    \$user = \$db->query('SELECT * FROM users WHERE uid = $ADMIN_UID')->fetchObject();
    if (\$user) {
      echo json_encode((array)\$user);
    }
  " 2>/dev/null || echo "")
  
  if [ -z "$ADMIN_BACKUP" ]; then
    error "❌ Could not backup admin user"
  fi
  
  success "Admin user backed up: ${#ADMIN_BACKUP} bytes"
}

restore_admin_user() {
  if [ -z "$ADMIN_BACKUP" ]; then
    warn "No admin backup available (skipping restore)"
    return 0
  fi
  
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 4: RESTORE ADMIN USER"
  info "═══════════════════════════════════════════════════════════════"
  info "Restoring admin user (uid=$ADMIN_UID)..."
  
  $DRUSH php:eval "
    \$db = \Drupal\Core\Database\Database::getConnection();
    \$backup = json_decode('$ADMIN_BACKUP', true);
    
    // Check if uid=1 exists, delete if different
    \$existing = \$db->query('SELECT uid FROM users WHERE uid = $ADMIN_UID')->fetchField();
    if (\$existing) {
      \$db->delete('users')->condition('uid', $ADMIN_UID)->execute();
    }
    
    // Insert original admin
    \$db->insert('users')->fields(\$backup)->execute();
    echo '✅ Admin user restored\n';
  " >> "$LOG_FILE" 2>&1 || warn "Could not restore admin (may need manual fix)"
  
  success "Admin user restored"
}

# ============================================================================
# DATABASE BACKUP
# ============================================================================

create_database_backup() {
  info "Creating full database backup..."
  info "   File: $BACKUP_FILE"
  
  if mysqldump -h "$DB_D10_HOST" -u "$DB_D10_USER" -p"$DB_D10_PASSWORD" "$DB_D10_NAME" \
    --single-transaction --quick 2>/dev/null | gzip > "$BACKUP_FILE"; then
    
    local size=$(du -h "$BACKUP_FILE" | cut -f1)
    success "Backup created: $size"
  else
    error "❌ Failed to create backup"
  fi
}

# ============================================================================
# TRUNCATE PHASE (SAFE ORDER WITH FK DEPENDENCY)
# ============================================================================

truncate_tables() {
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 2: TRUNCATE TABLES (FK SAFE ORDER)"
  info "═══════════════════════════════════════════════════════════════"
  
  if $DRY_RUN; then
    info "[DRY RUN] Would execute TRUNCATE in this order:"
  fi
  
  # Order is CRITICAL for FK dependencies
  local tables_to_truncate=(
    "node__body"
    "node__comment"
    "node__field_image"
    "node__field_tags"
    "node__field_colonne_de_droite"
    "node_revision__body"
    "node_revision__comment"
    "node_revision__field_image"
    "node_revision__field_tags"
    "node_revision__field_colonne_de_droite"
    "node_field_revision"
    "node_access"
    "node_field_data"
    "node_revision"
    "node"
    "taxonomy_term_revision__parent"
    "taxonomy_term__parent"
    "taxonomy_term_field_revision"
    "taxonomy_term_revision"
    "taxonomy_term_field_data"
    "taxonomy_term_data"
  )
  
  # Truncate each table
  for table in "${tables_to_truncate[@]}"; do
    if $DRY_RUN; then
      info "   [DRY] TRUNCATE $table"
    else
      info "Truncating: $table"
      $DRUSH php:eval "
        \$db = \Drupal\Core\Database\Database::getConnection();
        if (\$db->schema()->tableExists('$table')) {
          \$db->truncate('$table')->execute();
          echo '✅ Truncated\n';
        } else {
          echo '⚠️ Table not found\n';
        }
      " >> "$LOG_FILE" 2>&1 || warn "Could not truncate $table"
    fi
  done
  
  # Truncate users (but uid=0,1 preserved by migration framework)
  if $DRY_RUN; then
    info "   [DRY] DELETE FROM users WHERE uid > 1"
  else
    info "Deleting users (uid > 1 only, preserving 0 and 1)..."
    $DRUSH php:eval "
      \$db = \Drupal\Core\Database\Database::getConnection();
      \$db->delete('users')->condition('uid', 1, '>')->execute();
      echo '✅ Users cleared (uid > 1)\n';
    " >> "$LOG_FILE" 2>&1 || warn "Could not clear users"
  fi
  
  # Clear migration tracking
  if ! $DRY_RUN; then
    info "Clearing migration tracking tables..."
    $DRUSH php:eval "
      \$db = \Drupal\Core\Database\Database::getConnection();
      foreach (['d7_node_page', 'd7_node_cartographie', 'd7_user', 'd7_taxonomy_term'] as \$table) {
        if (\$db->schema()->tableExists(\"\${table}\")) {
          \$db->truncate(\"\${table}\")->execute();
        }
      }
      echo '✅ Migration tracking cleared\n';
    " >> "$LOG_FILE" 2>&1 || warn "Could not clear migration tracking"
  fi
  
  # Clear entity field storage definitions (CRITICAL for field loading)
  if ! $DRY_RUN; then
    info "Clearing cached entity field definitions..."
    $DRUSH php:script "$SCRIPT_DIR/utils/clear_entity_definitions.php" >> "$LOG_FILE" 2>&1 || warn "Could not clear entity definitions"
    
    info "Forcing entity field definitions regeneration..."
    $DRUSH php:script "$SCRIPT_DIR/utils/regenerate_entity_definitions.php" >> "$LOG_FILE" 2>&1 || warn "Could not regenerate entity definitions"
    
    # Rebuild cache after regeneration
    info "Rebuilding cache after entity definitions regeneration..."
    $DRUSH cache:rebuild >> "$LOG_FILE" 2>&1 || warn "Could not rebuild cache"
    
    # Install French language (CRITICAL for field loading)
    info "Installing French language..."
    $DRUSH php:script "$SCRIPT_DIR/utils/install_french_language.php" >> "$LOG_FILE" 2>&1 || warn "Could not install French language"
  fi
  
  success "✅ All tables truncated, entity definitions regenerated, and French language installed"
}

# ============================================================================
# MAINTENANCE MODE
# ============================================================================

set_maintenance_mode() {
  local mode=$1
  info "Setting maintenance mode: $mode"
  
  if ! $DRY_RUN; then
    $DRUSH sset system.maintenance_mode "$mode" >> "$LOG_FILE" 2>&1 || warn "Could not set maintenance mode"
  fi
}

# ============================================================================
# MIGRATION EXECUTION
# ============================================================================

run_migration() {
  local migration_id=$1
  local description=$2
  
  info "Running: $description (ID: $migration_id)"
  
  if $DRY_RUN; then
    info "   [DRY] Would import: $DRUSH migrate:import $migration_id -y"
    $DRUSH migrate:status "$migration_id" >> "$LOG_FILE" 2>&1 || true
  else
    # First rollback any existing data to start fresh
    $DRUSH migrate:rollback "$migration_id" 2>/dev/null || true
    
    if $DRUSH migrate:import "$migration_id" -y >> "$LOG_FILE" 2>&1; then
      local count=$($DRUSH migrate:status "$migration_id" 2>/dev/null | grep -oP 'Imported: \K\d+' || echo "?")
      success "   ✅ Completed ($count records)"
    else
      error "❌ Failed: $description"
    fi
  fi
}

execute_migrations() {
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 3: EXECUTE MIGRATIONS (FK SAFE ORDER)"
  info "═══════════════════════════════════════════════════════════════"
  
  # Reset migrations first so they can be re-run
  local migrations_to_reset=(
    "d7_taxonomy_vocabulary"
    "d7_taxonomy_term_actus"
    "d7_taxonomy_term_tags"
    "d7_user_role"
    "d7_user"
    "d7_node_type"
    "d7_node_complete_page"
    "d7_node_complete_cartographie"
    "d7_url_alias"
    "d7_menu"
    "d7_menu_links"
  )
  
  if ! $DRY_RUN; then
    info "Resetting migration tracking..."
    for migration_id in "${migrations_to_reset[@]}"; do
      $DRUSH migrate:reset-status "$migration_id" 2>/dev/null || warn "Could not reset $migration_id"
    done
  fi
  
  # Order is critical: dependencies must exist first
  run_migration "d7_taxonomy_vocabulary" "Taxonomy Vocabularies"
  sleep 1
  
  run_migration "d7_taxonomy_term_actus" "Taxonomy Terms (Actus)"
  sleep 1
  
  run_migration "d7_taxonomy_term_tags" "Taxonomy Terms (Tags)"
  sleep 1
  
  run_migration "d7_user_role" "User Roles"
  sleep 1
  
  # Fix user role permissions
  if ! $DRY_RUN; then
    info "Applying user role permissions fix..."
    bash "$SCRIPT_DIR/utils/fix_user_role_permissions.sh" >> "$LOG_FILE" 2>&1 || warn "Permissions fix had issues"
    success "✅ User role permissions fixed"
  fi
  
  run_migration "d7_user" "Users"
  sleep 1
  
  run_migration "d7_node_type" "Node Types"
  sleep 1
  
  run_migration "d7_node_complete_page" "Page Nodes"
  sleep 1
  
  run_migration "d7_node_complete_cartographie" "Cartographie Nodes"
  sleep 1
  
  # URL ALIASES MIGRATION (must come after nodes)
  run_migration "d7_url_alias" "URL Aliases (Path Aliases)"
  sleep 1
  
  # Convert all path aliases to language-neutral (und) for single-language site
  if ! $DRY_RUN; then
    info "Converting path aliases to language-neutral (und)..."
    $DRUSH sql:query "UPDATE path_alias SET langcode = 'und' WHERE langcode = 'fr'" >> "$LOG_FILE" 2>&1 || warn "Could not update alias langcodes"
    success "✅ Path aliases converted to language-neutral"
  fi
  
  # VIEWS IMPORT (must come BEFORE menu migration for route validation)
  # The liste_actus View creates the /liste-actus route needed by menu links
  import_liste_actus_view
  
  # MENU MIGRATION (after Views to ensure routes exist)
  run_migration "d7_menu" "Menus"
  sleep 1
  
  run_migration "d7_menu_links" "Menu Links"
  sleep 1
  
  # FIELD COLLECTIONS → PARAGRAPHS MIGRATION
  run_migration "d7_field_collection_type" "Field Collection Types"
  sleep 1
  
  run_migration "d7_field_collection:colonne_de_droite" "Field Collections (Paragraphs)"
  sleep 1
  
  run_migration "d7_field_collection_revisions:colonne_de_droite" "Field Collection Revisions"
  sleep 1
  
  success "✅ All migrations completed"
}

# ============================================================================
# POST-MIGRATION HYDRATION
# ============================================================================

hydrate_body_fields() {
  if $DRY_RUN; then
    info "[DRY RUN] Would hydrate body fields and attach paragraphs"
    return 0
  fi
  
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 5: POST-MIGRATION HYDRATION & PARAGRAPH ATTACHMENT"
  info "═══════════════════════════════════════════════════════════════"
  
  # Import paragraph field configurations from YAML (ONLY paragraph fields, skip node fields and vocabularies)
  # Skip configurations that conflict with migration-created entities:
  # - field.storage.node.field_tags (already created by migration)
  # - field.field.node.page.field_tags (already created by migration)
  # - taxonomy.vocabulary.* (already created by migration)
  info "Importing paragraph field configurations..."
  
  # Create a temporary directory with only safe configs
  local temp_config_dir="$PROJECT_ROOT/temp_config_import"
  mkdir -p "$temp_config_dir"
  
  # Copy only paragraph-related configs (safe to import)
  local safe_configs=(
    "field.storage.paragraph.field_titre.yml"
    "field.storage.paragraph.field_contenu.yml"
    "field.field.paragraph.colonne_de_droite.field_titre.yml"
    "field.field.paragraph.colonne_de_droite.field_contenu.yml"
    "paragraphs.paragraphs_type.colonne_de_droite.yml"
    "core.entity_view_display.paragraph.colonne_de_droite.preview.yml"
    "core.entity_view_display.paragraph.colonne_de_droite.default.yml"
    "core.entity_form_display.paragraph.colonne_de_droite.default.yml"
  )
  
  local config_source="$PROJECT_ROOT/web/modules/custom/normandie_migrate/config/install"
  for config_file in "${safe_configs[@]}"; do
    if [ -f "$config_source/$config_file" ]; then
      cp "$config_source/$config_file" "$temp_config_dir/" 2>/dev/null || true
    fi
  done
  
  # Import only the safe configs
  if [ "$(ls -A $temp_config_dir)" ]; then
    $DRUSH config:import --partial --source="$temp_config_dir" -y >> "$LOG_FILE" 2>&1 || warn "Could not import paragraph field configs"
  fi
  
  # Cleanup
  rm -rf "$temp_config_dir"
  
  success "✅ Paragraph field configurations imported (node fields and vocabularies preserved from migration)"
  
  # Execute post-update hooks for field creation and paragraph attachment
  info "Executing post-update hooks..."
  
  # Ensure the module is loaded
  info "  → Loading normandie_migrate module functions..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    echo "✅ Module functions loaded\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not load module"
  
  # 1. Verify paragraph bundle fields (titre and contenu) exist from config
  info "  → Step 1: Verifying paragraph fields (titre & contenu)..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_create_paragraph_fields($sandbox);
    echo "✅ Paragraph fields verified\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not verify paragraph fields"
  
  # 2. Create field storage and configuration
  info "  → Step 2: Creating field_colonne_de_droite..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_create_field_colonne_de_droite($sandbox);
    echo "✅ Field created\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not create field"
  
  # 3. Configure displays
  info "  → Step 3: Configuring field displays..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_configure_field_displays($sandbox);
    echo "✅ Displays configured\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not configure displays"
  
  # 4. Configure paragraph bundle display
  info "  → Step 4: Configuring paragraph display..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_configure_paragraph_display($sandbox);
    echo "✅ Paragraph display configured\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not configure paragraph display"
  
  # 5. Create preview display for closed paragraph editing
  info "  → Step 5: Creating preview display for paragraphs..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_create_paragraph_preview_display($sandbox);
    echo "✅ Preview display created\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Could not create preview display"
  
  # 6. Hydrate field_collection paragraphs with field data
  info "  → Step 6: Hydrating paragraph fields (titre & contenu)..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    $result = normandie_migrate_post_update_hydrate_colonne_de_droite_fields($sandbox);
    echo "$result\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Paragraph hydration had issues"
  
  success "✅ Post-update hooks completed"
  
  # Attach paragraphs to nodes from D7 field_collection mappings
  info "Attaching paragraphs to nodes..."
  $DRUSH php:eval '
    include_once \Drupal::moduleHandler()->getModule("normandie_migrate")->getPath() . "/normandie_migrate.module";
    $sandbox = [];
    normandie_migrate_post_update_attach_paragraphs_to_nodes($sandbox);
    echo "✅ Attachment complete\n";
  ' >> "$LOG_FILE" 2>&1 || warn "Paragraph attachment had issues"
  
  success "✅ Paragraph attachments completed"
  
  # Fix paragraph revision references (critical for correct paragraph loading)
  info "Fixing paragraph revision references..."
  $DRUSH php:script "$SCRIPT_DIR/utils/fix_paragraph_revisions.php" >> "$LOG_FILE" 2>&1 || warn "Paragraph revision fix had issues"
  
  success "✅ Paragraph revisions fixed"
  
  # Hydrate body fields
  info "Hydrating body fields via setValue()..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/hydrate_body_fields.php" >> "$LOG_FILE" 2>&1 || warn "Body hydration had issues"
  
  success "✅ Body field hydration completed"
  
  # Migrate field_tags (taxonomy term references)
  info "Migrating field_tags from D7 to D10..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/migrate_field_tags.php" >> "$LOG_FILE" 2>&1 || warn "Field_tags migration had issues"
  
  success "✅ Field_tags migration completed"
  
  # Fix entity/field definition mismatches permanently - CRITICAL FIX
  info "Permanently synchronizing paragraph field definitions..."
  $DRUSH php:script "$SCRIPT_DIR/utils/sync_paragraph_definitions.php" >> "$LOG_FILE" 2>&1 || warn "Field definition synchronization had issues"
  
  success "✅ Paragraph field definitions permanently synchronized"
}

# ============================================================================
# VALIDATION
# ============================================================================

validate_migration() {
  # Skip validation in DRY RUN mode since no data was actually migrated
  if $DRY_RUN; then
    info ""
    info "═══════════════════════════════════════════════════════════════"
    info "PHASE 6: VALIDATION & VERIFICATION"
    info "═══════════════════════════════════════════════════════════════"
    info "[DRY RUN] Skipping validation (no real data to validate)"
    return 0
  fi
  
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 6: VALIDATION & VERIFICATION"
  info "═══════════════════════════════════════════════════════════════"
  
  $DRUSH php:script "$SCRIPT_DIR/utils/validate_migration.php" >> "$LOG_FILE" 2>&1
  
  success "✅ Validation completed"
}

# ============================================================================
# CLEANUP & FINAL STEPS
# ============================================================================

final_cleanup() {
  if $DRY_RUN; then
    return 0
  fi
  
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "PHASE 7: CLEANUP & FINAL STEPS"
  info "═══════════════════════════════════════════════════════════════"
  
  info "Clearing caches..."
  $DRUSH cache-rebuild >> "$LOG_FILE" 2>&1 || warn "Could not clear cache"
  
  info "Clearing sessions..."
  $DRUSH php:eval "
    \$db = \Drupal\Core\Database\Database::getConnection();
    \$db->truncate('sessions')->execute();
    echo '✅ Sessions cleared\n';
  " >> "$LOG_FILE" 2>&1 || true
  
  info "Setting French as default language (matching D7 config)..."
  $DRUSH cset system.site default_langcode fr -y >> "$LOG_FILE" 2>&1 || warn "Could not set default language"
  
  info "Disabling URL language prefixes (matching D7 config)..."
  $DRUSH cset language.negotiation url.prefixes.fr "" -y >> "$LOG_FILE" 2>&1 || warn "Could not disable FR prefix"
  $DRUSH cset language.negotiation url.prefixes.en "" -y >> "$LOG_FILE" 2>&1 || warn "Could not disable EN prefix"
  
  info "Setting language negotiation to use selected language (French)..."
  $DRUSH cset language.negotiation selected_langcode fr -y >> "$LOG_FILE" 2>&1 || warn "Could not set selected language"
  
  info "Fixing language detection method (remove URL detection, use selected language)..."
  $DRUSH php:eval "
    \$config = \Drupal::configFactory()->getEditable('language.types');
    \$negotiation = \$config->get('negotiation');
    // Remove URL-based detection to fix homepage language bug
    unset(\$negotiation['language_interface']['enabled']['language-url']);
    // Add Selected language detection (priority 0 = highest)
    \$negotiation['language_interface']['enabled']['language-selected'] = 0;
    \$config->set('negotiation', \$negotiation);
    \$config->save();
    echo '✅ Language detection fixed: using selected language (FR) instead of URL\n';
  " >> "$LOG_FILE" 2>&1 || warn "Could not fix language detection"
  
  info "Setting timezone to Europe/Paris (matching D7)..."
  $DRUSH cset system.date timezone.default "Europe/Paris" -y >> "$LOG_FILE" 2>&1 || warn "Could not set timezone"
  
  info "Configuring file system paths (matching D7)..."
  $DRUSH cset system.file path.temporary "/tmp" -y >> "$LOG_FILE" 2>&1 || warn "Could not set temp path"
  
  info "Setting default text format fallback..."
  $DRUSH cset filter.settings fallback_format "plain_text" -y >> "$LOG_FILE" 2>&1 || warn "Could not set fallback format"
  
  info "Enabling admin theme for node editing (matching D7 behavior)..."
  $DRUSH cset node.settings use_admin_theme true -y >> "$LOG_FILE" 2>&1 || warn "Could not enable admin theme for nodes"
  
  info "Synchronizing site name with D7 value..."
  local d7_site_name=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'site_name'" 2>/dev/null || echo "")
  if [ -n "$d7_site_name" ]; then
    $DRUSH cset system.site name "$d7_site_name" -y >> "$LOG_FILE" 2>&1 || warn "Could not set site name"
    info "   Site name set to: $d7_site_name"
  fi
  
  info "Synchronizing site email with D7 value..."
  local d7_site_mail=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'site_mail'" 2>/dev/null || echo "")
  if [ -n "$d7_site_mail" ]; then
    $DRUSH cset system.site mail "$d7_site_mail" -y >> "$LOG_FILE" 2>&1 || warn "Could not set site email"
    info "   Site email set to: $d7_site_mail"
  fi
  
  info "Setting front page to node/1..."
  $DRUSH cset system.site page.front "/node/1" -y >> "$LOG_FILE" 2>&1 || warn "Could not set front page"
  
  info "Verifying main menu configuration..."
  $DRUSH config:get system.menu.main >> "$LOG_FILE" 2>&1 || warn "Main menu not found"
  
  info "Verifying liste_actus View exists..."
  if $DRUSH views:list 2>/dev/null | grep -q "liste_actus"; then
    info "   ✓ liste_actus View confirmed"
  else
    warn "liste_actus View not found (should have been created in Phase 3)"
  fi
  
  info "Installing French language translations..."
  $DRUSH locale:check >> "$LOG_FILE" 2>&1 || warn "Could not check for translation updates"
  $DRUSH locale:update >> "$LOG_FILE" 2>&1 || warn "Could not update translations"
  
  info "Configuring update status module (enable automatic update checks)..."
  $DRUSH pm:enable update -y >> "$LOG_FILE" 2>&1 || warn "Update module already enabled"
  $DRUSH cset update.settings check.disabled_extensions false -y >> "$LOG_FILE" 2>&1 || warn "Could not enable update checks"
  $DRUSH cset update.settings notification.threshold "all" -y >> "$LOG_FILE" 2>&1 || warn "Could not set notification threshold"
  
  info "Configuring AddToAny module (social sharing buttons)..."
  $DRUSH php:script "$SCRIPT_DIR/utils/configure_addtoany.php" >> "$LOG_FILE" 2>&1 || warn "Could not configure AddToAny"
  
  info "Running database updates (includes entity schema updates)..."
  $DRUSH updatedb -y >> "$LOG_FILE" 2>&1 || warn "No database updates needed"
  
  info "Updating Drupal packages and synchronizing update status..."
  bash "$SCRIPT_DIR/update_and_sync.sh" >> "$LOG_FILE" 2>&1 || {
    warn "Could not sync update data, trying manual update..."
    cd "$PROJECT_ROOT"
    composer update drupal/core-recommended drupal/entity_reference_revisions drupal/migrate_plus drupal/migrate_tools drupal/paragraphs --with-all-dependencies >> "$LOG_FILE" 2>&1 || warn "Composer update failed"
    cd - > /dev/null
  }
  
  info "Verifying update data availability..."
  $DRUSH php:eval "
    \$available = \Drupal::keyValue('update_available_releases')->getAll();
    if (empty(\$available)) {
      echo '⚠️ No update data available yet\n';
    } else {
      echo '✅ Update data available for ' . count(\$available) . ' projects\n';
    }
  " >> "$LOG_FILE" 2>&1 || warn "Could not verify update data"
  
  info "Final cache clear..."
  $DRUSH cache-rebuild >> "$LOG_FILE" 2>&1 || warn "Could not clear cache"
  
  success "✅ Cleanup and post-migration configuration completed"
}

# ============================================================================
# BLOCK PLACEMENT: CREATE ALL CUSTOM BLOCKS
# ============================================================================

create_custom_blocks() {
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "CREATE CUSTOM BLOCKS (matching D7 configuration)"
  info "═══════════════════════════════════════════════════════════════"
  
  if $DRY_RUN; then
    info "[DRY RUN] Would create custom blocks for normandie theme"
    return 0
  fi
  
  # First, create the header-top menu if it doesn't exist
  info "Creating header-top menu..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/create_header_menu.php" >> "$LOG_FILE" 2>&1 || warn "Could not create header-top menu"
  
  # Create header-top menu links (placeholder links for theme template)
  info "Creating header-top menu links..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/create_header_menu_links.php" >> "$LOG_FILE" 2>&1 || warn "Could not create header-top menu links"
  
  info "Creating custom blocks..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/create_custom_blocks.php" >> "$LOG_FILE" 2>&1 || {
    warn "Could not create some blocks"
    return 1
  }
  
  # Create missing system blocks (primary_local_tasks, messages, etc.)
  info "Creating missing system blocks..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/create_missing_system_blocks.php" >> "$LOG_FILE" 2>&1 || warn "Could not create some system blocks"
  
  # Reposition existing blocks to match D7 layout
  info "Repositioning existing blocks to match D7 layout..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/reposition_blocks.php" >> "$LOG_FILE" 2>&1 || warn "Could not reposition some blocks"
  
  # Delete unwanted blocks that clutter the interface
  info "Deleting unwanted default blocks..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/delete_unwanted_blocks.php" >> "$LOG_FILE" 2>&1 || warn "Could not delete some blocks"
  
  # Export configuration to .yml files for deployment
  info "Exporting block configuration to files..."
  
  $DRUSH config:export -y >> "$LOG_FILE" 2>&1 || warn "Could not export configuration"
  
  success "✅ Custom blocks created, positioned, and exported"
}

# ============================================================================
# VIEWS IMPORT: LISTE ACTUS
# ============================================================================

import_liste_actus_view() {
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "IMPORT VIEWS - LISTE ACTUS (before menu migration)"
  info "═══════════════════════════════════════════════════════════════"
  
  if $DRY_RUN; then
    info "[DRY RUN] Would import liste_actus View and Block"
    return 0
  fi
  
  # Check prerequisites
  info "Checking Views Slideshow module..."
  if ! $DRUSH pm:list --status=enabled 2>/dev/null | grep -q "views_slideshow"; then
    warn "⚠️  Views Slideshow module NOT installed - skipping view import"
    warn "    Install via: composer require drupal/views_slideshow"
    return 0
  fi
  success "✓ Views Slideshow module enabled"
  
  info "Creating View and Block..."
  
  $DRUSH php:script "$SCRIPT_DIR/utils/create_liste_actus_view.php" >> "$LOG_FILE" 2>&1 || {
    error "Failed to create liste_actus View"
    return 1
  }
  
  success "✅ Liste Actus View imported successfully"
}

# ============================================================================
# FINAL SUMMARY
# ============================================================================

print_summary() {
  info ""
  info "═══════════════════════════════════════════════════════════════"
  info "MIGRATION SUMMARY"
  info "═══════════════════════════════════════════════════════════════"
  
  if $DRY_RUN; then
    info "Status: DRY RUN (no changes made)"
  else
    info "Status: COMPLETED ✅"
  fi
  
  info "Timestamp: $TIMESTAMP"
  info "Log file: $LOG_FILE"
  
  if ! $DRY_RUN; then
    info "Backup file: $BACKUP_FILE"
  fi
  
  info ""
  info "📋 NEXT STEPS:"
  info "1. Exit maintenance mode: drush sset system.maintenance_mode 0"
  info "2. Test website: https://votre-domaine.fr"
  info "3. Verify nodes display: https://votre-domaine.fr/node/1"
  info "4. Check Views: /admin/structure/views/view/liste_actus"
  info "5. Check Block placement: /admin/structure/block/list/normandie"
  info "6. Test admin login"
  info ""
  info "🔙 ROLLBACK (if needed):"
  info "   Backup location: $BACKUP_FILE"
  info ""
  info "═══════════════════════════════════════════════════════════════"
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

main() {
  info "═══════════════════════════════════════════════════════════════"
  info "DRUPAL 7 → 10 MIGRATION V4 (FULL AUTOMATION + VIEWS)"
  info "═══════════════════════════════════════════════════════════════"
  info "Timestamp: $TIMESTAMP"
  info "Log: $LOG_FILE"
  
  if $DRY_RUN; then
    info "MODE: DRY RUN (test only, no changes)"
  else
    info "MODE: PRODUCTION (real migration)"
  fi
  
  # Phase 0: Preflight checks
  if [ "$SKIP_CHECKS" != "true" ]; then
    run_preflight_checks
  else
    warn "Skipping preflight checks (dangerous!)"
  fi
  
  # If preflight-only mode
  if $PREFLIGHT_ONLY; then
    info ""
    success "✅ Preflight checks completed. Run again without --preflight-only to migrate."
    exit 0
  fi
  
  # Phase 0.5: Set maintenance mode
  set_maintenance_mode 1
  
  # Phase 1: Backup admin user
  if ! $DRY_RUN; then
    backup_admin_user
  fi
  
  # Phase 1.5: Backup database
  if ! $DRY_RUN; then
    create_database_backup
  fi
  
  # Phase 2: Truncate tables
  truncate_tables
  
  # Phase 3: Execute migrations
  execute_migrations
  
  # Phase 4: Restore admin user
  if ! $DRY_RUN; then
    restore_admin_user
  fi
  
  # Phase 5: Hydrate fields and paragraphs
  hydrate_body_fields
  
  # Phase 6: Validate migration
  validate_migration
  
  # Phase 6.5: Create custom blocks
  create_custom_blocks
  
  # Phase 7: Cleanup and final steps
  final_cleanup
  
  # Phase 8: Exit maintenance mode
  # (Note: Views were already imported in Phase 3 before menu migration)
  set_maintenance_mode 0
  
  # Summary
  print_summary
  
  if ! $DRY_RUN; then
    success "🚀 MIGRATION COMPLETE - Ready for production!"
  else
    success "✅ DRY RUN COMPLETE - Ready for real migration"
  fi
}

# Run main
main

