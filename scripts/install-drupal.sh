#!/bin/bash

################################################################################
# DRUPAL 10 INSTALLATION SCRIPT
# Installs Drupal 10 using credentials from .env file
# 
# Usage: bash scripts/install-drupal.sh
################################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}║              DRUPAL 10 INSTALLATION WIZARD                    ║${NC}"
echo -e "${CYAN}║                                                               ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Load .env file
if [ ! -f "$PROJECT_ROOT/.env" ]; then
  echo -e "${RED}❌ Fichier .env introuvable${NC}"
  echo -e "${YELLOW}Veuillez créer le fichier .env à partir de .env.example${NC}"
  exit 1
fi

echo -e "${YELLOW}⏳ Chargement de la configuration depuis .env...${NC}"
set -a
source "$PROJECT_ROOT/.env"
set +a

# Verify required variables
if [ -z "$DB_D10_PASSWORD" ] || [ -z "$DB_D10_USER" ] || [ -z "$DB_D10_NAME" ]; then
  echo -e "${RED}❌ Variables manquantes dans .env${NC}"
  echo -e "${YELLOW}Assurez-vous que DB_D10_USER, DB_D10_PASSWORD et DB_D10_NAME sont définis${NC}"
  exit 1
fi

echo -e "${GREEN}✅ Configuration chargée${NC}"

# Generate admin password
ADMIN_PASS=$(openssl rand -base64 20)

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}🔐 CREDENTIALS ADMIN (À CONSERVER !):${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "Username: ${GREEN}admin${NC}"
echo -e "Password: ${GREEN}$ADMIN_PASS${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}⚠️  COPIEZ CES IDENTIFIANTS MAINTENANT !${NC}"
read -p "Appuyez sur Entrée après avoir copié les identifiants..."

# Test database connection
echo ""
echo -e "${YELLOW}⏳ Test de connexion à la base de données...${NC}"
if mysql -u "$DB_D10_USER" -p"$DB_D10_PASSWORD" -h "${DB_D10_HOST:-localhost}" -P "${DB_D10_PORT:-3306}" "$DB_D10_NAME" -e "SELECT 1;" > /dev/null 2>&1; then
  echo -e "${GREEN}✅ Connexion réussie${NC}"
else
  echo -e "${RED}❌ Échec de connexion à la base de données${NC}"
  echo -e "${RED}Vérifiez les identifiants dans le fichier .env${NC}"
  exit 1
fi

echo ""
echo -e "${YELLOW}⏳ Installation de Drupal 10 en cours...${NC}"
echo ""

cd "$PROJECT_ROOT"

# Install Drupal using drush with --existing-config to preserve settings.php
if ./vendor/bin/drush site:install standard \
  --db-url="mysql://${DB_D10_USER}:${DB_D10_PASSWORD}@${DB_D10_HOST:-localhost}:${DB_D10_PORT:-3306}/${DB_D10_NAME}" \
  --site-name="Normandie Habitat & Energie" \
  --account-name=admin \
  --account-pass="$ADMIN_PASS" \
  --locale=fr \
  -y 2>&1 | tee /tmp/drush_install.log; then
  
  echo ""
  echo -e "${GREEN}✅ Installation terminée avec succès !${NC}"
  echo ""
  echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
  echo -e "${GREEN}CONNEXION ADMIN:${NC}"
  echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
  echo -e "Username: ${GREEN}admin${NC}"
  echo -e "Password: ${GREEN}$ADMIN_PASS${NC}"
  echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}"
  echo ""
  echo -e "${YELLOW}Prochaine étape:${NC}"
  echo -e "  ${CYAN}bash scripts/post-installation.sh${NC}"
  echo ""
  
else
  echo ""
  echo -e "${RED}❌ Erreur lors de l'installation${NC}"
  echo -e "${YELLOW}Consultez les logs: /tmp/drush_install.log${NC}"
  exit 1
fi
