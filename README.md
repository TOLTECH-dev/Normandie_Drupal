# Normandie Drupal 10

> Plateforme web du dispositif Chèque éco énergie Normandie - Drupal 10.6+

## Prérequis

| Composant | Version |
|-----------|---------|
| PHP | 8.2+ |
| MariaDB | 10.11.11 |
| Composer | 2.x |
| Apache | 2.4+ |

**Extensions PHP requises :** `pdo_mysql`, `gd`, `json`, `curl`, `xml`, `zip`, `opcache`, `mbstring`

## Installation

### 1. Configurer la base de données

```bash
# Générer un mot de passe sécurisé
DB_PASSWORD=$(openssl rand -base64 32)
echo "Password: $DB_PASSWORD"

# Créer la base et l'utilisateur
mysql -u root -p << EOF
CREATE DATABASE normandie_d10_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE normandie_symfony CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'drupal'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON normandie_d10_prod.* TO 'drupal'@'localhost';
GRANT ALL PRIVILEGES ON normandie_symfony.* TO 'drupal'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

```bash
cp .env.example .env
nano .env
```

**Variables à définir dans `.env` :**

```ini
# Base de données Drupal 10
DB_D10_HOST=localhost
DB_D10_NAME=normandie_d10_prod
DB_D10_USER=drupal
DB_D10_PASSWORD=<votre_mot_de_passe>
DB_D10_PORT=3306

# Base de données Symfony
DB_SYMFONY_HOST=localhost
DB_SYMFONY_NAME=normandie_symfony
DB_SYMFONY_USER=drupal
DB_SYMFONY_PASSWORD=<votre_mot_de_passe>
DB_SYMFONY_PORT=3306

# Sécurité
HASH_SALT=<générer_avec: openssl rand -base64 32>

# Configuration SMTP
MAILER_DSN=smtp://localhost:1025  # MailHog (dev) ou smtp://user:pass@smtp.example.com:465 (prod)

```

### 4. Configurer le serveur web

**Apache VirtualHost exemple :**

```apache
<VirtualHost *:80>
    ServerName normandie.local
    DocumentRoot /var/www/normandie_d10/web
    
    <Directory /var/www/normandie_d10/web>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Configuration des trusted hosts dans `settings.php` :**

Éditer `web/sites/default/settings.php` et ajouter les patterns de domaines autorisés :

```php
/**
 * Trusted host configuration.
 * 
 * Protège contre les attaques Host Header par injection.
 * Ajoutez tous les domaines qui doivent accéder au site.
 */
$settings['trusted_host_patterns'] = [
  // Domaine local de développement
  '^normandie\.local$',
  '^www\.normandie\.local$',
  
  // Domaines de production (décommenter et adapter)
  // '^cheque-eco-energie\.normandie\.fr$',
  // '^www\.cheque-eco-energie\.normandie\.fr$',
];
```

> **⚠️ Important :** Sans cette configuration, Drupal affichera une erreur de sécurité. Les patterns utilisent des expressions régulières (échapper les points avec `\.`).

### 5. Installer Drupal

```bash
bash scripts/install-drupal.sh
```

Le script génère automatiquement les identifiants administrateur.

### 6. Configuration post-installation

```bash
bash scripts/post-installation.sh
```

Active les modules, configure le thème et met à jour les packages.

## Commandes utiles

### Drush

```bash
# Statut du site
./vendor/bin/drush status

# Vider le cache
./vendor/bin/drush cr

# Mettre à jour la base de données
./vendor/bin/drush updatedb -y
```

### Maintenance

```bash
# Activer le mode maintenance
./vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer

# Désactiver le mode maintenance
./vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer

# Backup base de données
./vendor/bin/drush sql:dump --gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Restaurer un backup
gunzip < backup.sql.gz | mysql -u drupal -p normandie_d10_prod
```

### Composer

```bash
# Mettre à jour tous les packages
composer update --with-all-dependencies

# Mettre à jour Drupal core uniquement
composer update drupal/core-recommended --with-dependencies

# Installer un nouveau module
composer require drupal/module_name
```

## Structure du projet

```
normandie_d10/
├── web/                    # Document root
│   ├── core/              # Drupal core
│   ├── modules/
│   │   ├── contrib/       # Modules communautaires
│   │   └── custom/        # Modules personnalisés
│   ├── themes/
│   │   └── custom/normandie/
│   └── sites/default/
├── vendor/                # Dépendances Composer
├── scripts/               # Scripts d'automatisation
├── backups/              # Sauvegardes automatiques
└── logs/                 # Logs d'exécution
```

## Modules personnalisés

| Module | Description |
|--------|-------------|
| `normandie_core` | Services de validation centralisés |
| `trouver_conseiller` | Recherche de conseillers énergétiques |
| `cartostructure` | Cartographie des structures |
| `carto` | Export PDF partenaires |
| `cartochantier` | Cartographie des chantiers |
| `chiffrescles` | Statistiques et métriques |
| `tarteaucitron` | Gestion des cookies RGPD |

## Technologies

- **Drupal** 10.6+ (LTS jusqu'en décembre 2026)
- **PHP** 8.2
- **MariaDB** 10.11.11
- **Bootstrap** 5
- **Leaflet** 1.9+ (cartographie)

## Bases de données

Le projet utilise 2 bases de données :

1. **normandie_d10_prod** - Base Drupal principale
2. **normandie_symfony** - Base métier pour modules custom

## Dépannage

### Site inaccessible

```bash
# Vérifier le mode maintenance
./vendor/bin/drush state:get system.maintenance_mode

# Désactiver et vider le cache
./vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer
./vendor/bin/drush cr
```

### Erreurs de cache

```bash
# Vider tous les caches
./vendor/bin/drush cr
```

### Problèmes de mise à jour

```bash
# Synchroniser les versions de packages
bash scripts/update_and_sync.sh

# Mettre à jour manuellement
composer update --with-all-dependencies
./vendor/bin/drush updatedb -y
./vendor/bin/drush cr
```

## Logs et débogage

```bash
# Logs Drupal (watchdog)
./vendor/bin/drush watchdog:show

# Logs Apache
tail -f /var/log/apache2/error.log
```

## Support

Pour toute question technique :

1. Vérifier les erreurs Drupal : `drush watchdog:show`
2. Consulter les logs serveur

---

**Version** : 1.0.0  
**Dernière mise à jour** : Janvier 2026  
**Projet** : Chèque éco énergie Normandie  
**Licence** : Propriétaire
