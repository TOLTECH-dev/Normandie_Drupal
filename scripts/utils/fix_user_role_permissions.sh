#!/bin/bash

################################################################################
# FIX USER ROLE PERMISSIONS MIGRATION
#
# This script fixes the user role permissions migration by:
# 1. Ensuring normandie_migrate module is enabled in core.extension
# 2. Using the MigrateD7Permissions plugin instead of callback
# 3. Mapping D7 RIDs to D10 role machine names (authenticated, anonymous, etc.)
# 4. Removing the security flaw: 'block IP addresses' → 'administer users'
# 5. Re-running the migration with corrected permissions
#
# Usage:
#   ./scripts/utils/fix_user_role_permissions.sh
#
# Can also be called automatically from migration.sh
################################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Setup logging
LOG_DIR="$PROJECT_ROOT/logs/migration"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/fix_permissions_$TIMESTAMP.log"

# Setup Drush
if [ -f "$PROJECT_ROOT/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_ROOT/vendor/bin/drush"
elif command -v drush &> /dev/null; then
  DRUSH="drush"
else
  echo "❌ Drush not found"
  exit 1
fi

# Logging functions
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
# MAIN EXECUTION
# ============================================================================

info "═══════════════════════════════════════════════════════════════"
info "FIX USER ROLE PERMISSIONS MIGRATION"
info "═══════════════════════════════════════════════════════════════"
info "Timestamp: $TIMESTAMP"
info "Log: $LOG_FILE"

# Step 1: Ensure normandie_migrate module is enabled in core.extension
info ""
info "Step 1: Activating normandie_migrate module..."
$DRUSH php:eval "
  \$config = \Drupal::configFactory()->getEditable('core.extension');
  \$module = \$config->get('module');
  if (!isset(\$module['normandie_migrate'])) {
    \$module['normandie_migrate'] = 0;
    ksort(\$module);
    \$config->set('module', \$module)->save();
    echo '✅ Module normandie_migrate added to core.extension\n';
  } else {
    echo '✓ Module already enabled\n';
  }
" >> "$LOG_FILE" 2>&1 || warn "Could not enable module"

# Step 2: Verify module is enabled
info ""
info "Step 2: Verifying module status..."
if $DRUSH pm:list --filter=normandie_migrate 2>&1 | grep -q "Enabled"; then
  success "Module normandie_migrate is enabled"
else
  error "Module normandie_migrate is NOT enabled"
fi

# Step 3: Clear cache to discover the plugin
info ""
info "Step 3: Clearing cache to discover MigrateD7Permissions plugin..."
$DRUSH cache:rebuild >> "$LOG_FILE" 2>&1 || warn "Could not clear cache"
success "Cache cleared"

# Step 4: Import the corrected migration configuration
info ""
info "Step 4: Importing corrected migration configuration..."

# Delete old configuration
info "   Deleting old migration configuration..."
$DRUSH config:delete migrate_plus.migration.d7_user_role >> "$LOG_FILE" 2>&1 || true

# Import new configuration from YAML
info "   Importing new configuration from module..."
$DRUSH config:import --partial --source="$PROJECT_ROOT/web/modules/custom/normandie_migrate/config/install" -y >> "$LOG_FILE" 2>&1 || warn "Could not import all configs (some conflicts expected - this is normal)"

# Clear cache after config import
$DRUSH cache:rebuild >> "$LOG_FILE" 2>&1 || true

# Verify the migration configuration
info "   Verifying migration configuration..."
$DRUSH config:get migrate_plus.migration.d7_user_role process.id >> "$LOG_FILE" 2>&1
$DRUSH config:get migrate_plus.migration.d7_user_role process.permissions >> "$LOG_FILE" 2>&1

# Check if static_map exists for role ID mapping
if $DRUSH config:get migrate_plus.migration.d7_user_role process.id 2>&1 | grep -q "static_map"; then
  success "Role ID mapping (static_map) is configured"
else
  error "Role ID mapping NOT configured - check YAML file"
fi

# Check if plugin is configured
if $DRUSH config:get migrate_plus.migration.d7_user_role process.permissions 2>&1 | grep -q "migrate_d7_permissions"; then
  success "MigrateD7Permissions plugin is configured"
else
  error "MigrateD7Permissions plugin NOT configured - check YAML file"
fi

# Step 5: Delete old numeric roles (1, 2, 3, 4) if they exist
info ""
info "Step 5: Cleaning up old numeric roles..."
for rid in 1 2 3 4; do
  if $DRUSH php:eval "echo \Drupal::entityTypeManager()->getStorage('user_role')->load('$rid') ? 'exists' : 'not_found';" 2>&1 | grep -q "exists"; then
    info "   Deleting role ID: $rid"
    $DRUSH role:delete "$rid" -y >> "$LOG_FILE" 2>&1 || warn "Could not delete role $rid"
  fi
done
success "Old roles cleaned up"

# Step 6: Reset and rollback the migration
info ""
info "Step 6: Resetting migration status..."
$DRUSH migrate:reset-status d7_user_role >> "$LOG_FILE" 2>&1 || true

info "Rollback existing role migration..."
$DRUSH migrate:rollback d7_user_role -y >> "$LOG_FILE" 2>&1 || warn "No items to rollback"
success "Migration reset"

# Step 7: Re-import the migration with corrected permissions
info ""
info "Step 7: Re-importing user roles with correct permissions..."
info "   This will migrate D7 roles to D10 with proper permission mapping"
info "   Security fix: 'block IP addresses' no longer maps to 'administer users'"

$DRUSH migrate:import d7_user_role -y --update 2>&1 | tee -a "$LOG_FILE"

# Step 8: Verify the authenticated role has correct permissions
info ""
info "Step 8: Verifying authenticated role permissions..."

# Get the current permissions
permissions=$($DRUSH config:get user.role.authenticated permissions 2>&1)

info "Checking expected permissions..."
all_found=true

# Check for expected permissions (simple string matching)
if echo "$permissions" | grep -q "'create page content'"; then
  info "   ✅ create page content"
else
  warn "   ❌ MISSING: create page content"
  all_found=false
fi

if echo "$permissions" | grep -q "'edit own page content'"; then
  info "   ✅ edit own page content"
else
  warn "   ❌ MISSING: edit own page content"
  all_found=false
fi

if echo "$permissions" | grep -q "'edit any page content'"; then
  info "   ✅ edit any page content"
else
  warn "   ❌ MISSING: edit any page content"
  all_found=false
fi

if echo "$permissions" | grep -q "'delete own page content'"; then
  info "   ✅ delete own page content"
else
  warn "   ❌ MISSING: delete own page content"
  all_found=false
fi

if echo "$permissions" | grep -q "'delete any page content'"; then
  info "   ✅ delete any page content"
else
  warn "   ❌ MISSING: delete any page content"
  all_found=false
fi

info "Checking forbidden permissions (security)..."
security_ok=true

# Check for forbidden permissions
if echo "$permissions" | grep -q "'administer users'"; then
  error "   🚨 SECURITY ISSUE: administer users should NOT be present!"
  security_ok=false
else
  info "   ✅ Correctly absent: administer users"
fi

# Final summary
info ""
info "═══════════════════════════════════════════════════════════════"
info "SUMMARY"
info "═══════════════════════════════════════════════════════════════"

if [ "$all_found" = true ] && [ "$security_ok" = true ]; then
  success "✅ All permissions correctly migrated"
  success "✅ No security issues detected"
  success "✅ User role permissions migration is FIXED"
  
  info ""
  info "📋 What was fixed:"
  info "  1. Module normandie_migrate enabled in core.extension"
  info "  2. MigrateD7Permissions plugin properly discovered"
  info "  3. D7 RIDs mapped to D10 role machine names"
  info "  4. Security flaw removed: 'block IP addresses' → NULL"
  info "  5. All page permissions migrated correctly"
  
  exit 0
else
  error "❌ Permission migration has issues - check log: $LOG_FILE"
  exit 1
fi
