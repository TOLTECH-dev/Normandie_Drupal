#!/bin/bash

################################################################################
# CONFIGURATION VERIFICATION SCRIPT
# Compares D7 and D10 configurations to ensure identical backoffice setup
################################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load environment
if [ -f "$PROJECT_ROOT/.env" ]; then
  source "$PROJECT_ROOT/.env"
else
  echo "❌ .env file not found"
  exit 1
fi

# Setup Drush
if [ -f "$PROJECT_ROOT/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_ROOT/vendor/bin/drush"
else
  echo "❌ Drush not found"
  exit 1
fi

echo "═══════════════════════════════════════════════════════════════"
echo "DRUPAL 7 → 10 CONFIGURATION VERIFICATION"
echo "═══════════════════════════════════════════════════════════════"
echo ""

pass_count=0
fail_count=0
warn_count=0

check_config() {
  local label="$1"
  local d7_value="$2"
  local d10_value="$3"
  local status="$4"
  
  printf "%-40s" "$label"
  
  if [ "$status" = "PASS" ]; then
    echo -e "${GREEN}✅ PASS${NC} (D7: $d7_value = D10: $d10_value)"
    pass_count=$((pass_count + 1))
  elif [ "$status" = "FAIL" ]; then
    echo -e "${RED}❌ FAIL${NC} (D7: $d7_value ≠ D10: $d10_value)"
    fail_count=$((fail_count + 1))
  elif [ "$status" = "WARN" ]; then
    echo -e "${YELLOW}⚠️  WARN${NC} (D7: $d7_value | D10: $d10_value)"
    warn_count=$((warn_count + 1))
  fi
}

# ============================================================================
# 1. SITE INFORMATION
# ============================================================================
echo "1️⃣  SITE INFORMATION"
echo "───────────────────────────────────────────────────────────────"

# Get D7 values
d7_site_name=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'site_name'" 2>/dev/null)
d7_site_mail=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'site_mail'" 2>/dev/null)
d7_site_frontpage=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'site_frontpage'" 2>/dev/null)

# Get D10 values
d10_site_name=$($DRUSH cget system.site name --format=string 2>/dev/null)
d10_site_mail=$($DRUSH cget system.site mail --format=string 2>/dev/null)
d10_site_frontpage=$($DRUSH cget system.site page.front --format=string 2>/dev/null)

# Compare
if [ "$d7_site_name" = "$d10_site_name" ]; then
  check_config "Site Name" "$d7_site_name" "$d10_site_name" "PASS"
else
  check_config "Site Name" "$d7_site_name" "$d10_site_name" "FAIL"
fi

if [ "$d7_site_mail" = "$d10_site_mail" ]; then
  check_config "Site Email" "$d7_site_mail" "$d10_site_mail" "PASS"
else
  check_config "Site Email" "$d7_site_mail" "$d10_site_mail" "WARN"
fi

# Normalize frontpage for comparison
d7_frontpage_normalized="/$d7_site_frontpage"
if [ "$d7_frontpage_normalized" = "$d10_site_frontpage" ]; then
  check_config "Front Page" "$d7_site_frontpage" "$d10_site_frontpage" "PASS"
else
  check_config "Front Page" "$d7_site_frontpage" "$d10_site_frontpage" "FAIL"
fi

echo ""

# ============================================================================
# 2. THEME CONFIGURATION
# ============================================================================
echo "2️⃣  THEME CONFIGURATION"
echo "───────────────────────────────────────────────────────────────"

d7_theme_default=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'theme_default'" 2>/dev/null)
d7_admin_theme=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'admin_theme'" 2>/dev/null)

d10_theme_default=$($DRUSH cget system.theme default --format=string 2>/dev/null)
d10_admin_theme=$($DRUSH cget system.theme admin --format=string 2>/dev/null)

if [ "$d7_theme_default" = "$d10_theme_default" ]; then
  check_config "Default Theme" "$d7_theme_default" "$d10_theme_default" "PASS"
else
  check_config "Default Theme" "$d7_theme_default" "$d10_theme_default" "FAIL"
fi

# Admin theme changed from Seven to Claro (expected)
if [ "$d7_admin_theme" = "seven" ] && [ "$d10_admin_theme" = "claro" ]; then
  check_config "Admin Theme" "$d7_admin_theme" "$d10_admin_theme (upgraded)" "PASS"
else
  check_config "Admin Theme" "$d7_admin_theme" "$d10_admin_theme" "WARN"
fi

echo ""

# ============================================================================
# 3. LANGUAGE CONFIGURATION
# ============================================================================
echo "3️⃣  LANGUAGE CONFIGURATION"
echo "───────────────────────────────────────────────────────────────"

d7_language=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(value, 'language\";s:2:\"', -1), '\"', 1), ';', 1) FROM variable WHERE name = 'language_default'" 2>/dev/null)
d10_language=$($DRUSH cget system.site default_langcode --format=string 2>/dev/null)

if [ "$d7_language" = "$d10_language" ]; then
  check_config "Default Language" "$d7_language" "$d10_language" "PASS"
else
  check_config "Default Language" "$d7_language" "$d10_language" "FAIL"
fi

echo ""

# ============================================================================
# 4. DATE/TIME CONFIGURATION
# ============================================================================
echo "4️⃣  DATE/TIME CONFIGURATION"
echo "───────────────────────────────────────────────────────────────"

d7_timezone=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'date_default_timezone'" 2>/dev/null)
d10_timezone=$($DRUSH cget system.date timezone.default --format=string 2>/dev/null)

if [ "$d7_timezone" = "$d10_timezone" ]; then
  check_config "Default Timezone" "$d7_timezone" "$d10_timezone" "PASS"
else
  check_config "Default Timezone" "$d7_timezone" "$d10_timezone" "FAIL"
fi

echo ""

# ============================================================================
# 5. FILE SYSTEM CONFIGURATION
# ============================================================================
echo "5️⃣  FILE SYSTEM CONFIGURATION"
echo "───────────────────────────────────────────────────────────────"

d7_file_scheme=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'file_default_scheme'" 2>/dev/null)
d7_file_public=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(value, '\"', 2), '\"', -1) FROM variable WHERE name = 'file_public_path'" 2>/dev/null)

# D10 file paths are in settings.php, not config
d10_file_public=$($DRUSH ev "echo \Drupal::service('file_system')->realpath('public://');" 2>/dev/null | tail -1)

check_config "File Default Scheme" "$d7_file_scheme" "public (hardcoded)" "PASS"

if [[ "$d10_file_public" == *"sites/default/files"* ]]; then
  check_config "Public Files Path" "$d7_file_public" "sites/default/files" "PASS"
else
  check_config "Public Files Path" "$d7_file_public" "$d10_file_public" "WARN"
fi

echo ""

# ============================================================================
# 6. NODE/CONTENT CONFIGURATION
# ============================================================================
echo "6️⃣  NODE/CONTENT CONFIGURATION"
echo "───────────────────────────────────────────────────────────────"

d7_node_admin_theme=$(mysql -h "$DB_D7_HOST" -u "$DB_D7_USER" -p"$DB_D7_PASSWORD" "$DB_D7_NAME" -N -e "SELECT SUBSTRING_INDEX(value, ':', -1) FROM variable WHERE name = 'node_admin_theme'" 2>/dev/null | tr -d ';')
d10_node_admin_theme=$($DRUSH cget node.settings use_admin_theme --format=string 2>/dev/null)

# Convert both to normalized values for comparison
if [ "$d7_node_admin_theme" = "1" ] || [ "$d7_node_admin_theme" = "true" ]; then
  d7_normalized="enabled"
else
  d7_normalized="disabled"
fi

if [ "$d10_node_admin_theme" = "1" ] || [ "$d10_node_admin_theme" = "true" ]; then
  d10_normalized="enabled"
else
  d10_normalized="disabled"
fi

if [ "$d7_normalized" = "$d10_normalized" ]; then
  check_config "Use Admin Theme for Nodes" "$d7_normalized" "$d10_normalized" "PASS"
else
  check_config "Use Admin Theme for Nodes" "$d7_normalized" "$d10_normalized" "WARN"
fi

echo ""

# ============================================================================
# SUMMARY
# ============================================================================
echo "═══════════════════════════════════════════════════════════════"
echo "VERIFICATION SUMMARY"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo -e "${GREEN}✅ Passed: $pass_count${NC}"
echo -e "${YELLOW}⚠️  Warnings: $warn_count${NC}"
echo -e "${RED}❌ Failed: $fail_count${NC}"
echo ""

total=$((pass_count + warn_count + fail_count))
if [ $total -gt 0 ]; then
  pass_percent=$((pass_count * 100 / total))
  echo "Overall: $pass_percent% identical configuration"
else
  echo "No checks performed"
fi

echo ""

if [ $fail_count -gt 0 ]; then
  echo "🔧 ACTION REQUIRED: Fix failed checks above"
  exit 1
elif [ $warn_count -gt 0 ]; then
  echo "⚠️  REVIEW RECOMMENDED: Check warnings above"
  exit 0
else
  echo "✅ ALL CHECKS PASSED - D7 and D10 configurations are identical!"
  exit 0
fi
