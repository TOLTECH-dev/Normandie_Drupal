# Migration Utility Scripts

This directory contains PHP utility scripts used by `migration.sh` to handle complex Drupal entity creation and configuration.

## Architecture Benefits

By separating PHP code into dedicated files instead of embedding it in bash scripts, we gain:

1. **No Bash Escaping Issues**: PHP code is written as plain PHP without worrying about bash quoting, escaping special characters, or variable interpolation conflicts.

2. **Maintainability**: Each script has a single, clear purpose and can be edited independently.

3. **Testability**: Scripts can be tested individually using `drush php:script`.

4. **Reusability**: Scripts can be called from multiple places or even outside the migration context.

5. **Readability**: Easier to read and understand than embedded heredocs or escaped strings.

## Available Scripts

### Block & Menu Management

#### 1. create_header_menu.php
- **Purpose**: Create the `header-top` menu entity
- **Output**: Creates Menu with id='header-top', label='Header Top Links'
- **Usage**: Called during block creation phase to ensure menu exists before blocks reference it

#### 2. create_header_menu_links.php
- **Purpose**: Create placeholder menu links for the header-top menu
- **Output**: Creates 2 MenuLinkContent entities:
  - "Region Normandie" - route:<nolink>
  - "Cheque Eco Energie" - route:<nolink>
- **Usage**: Called after header-top menu creation to populate with placeholder items

#### 3. create_custom_blocks.php
- **Purpose**: Create all 5 custom blocks for normandie theme
- **Output**: Creates blocks:
  - normandie_addtoany (navigation_top)
  - normandie_footer_menu (footer)
  - normandie_logo_block (navigation_before_top) - Critical fix for Header Logos
  - normandie_secondary_menu (subnavigation)
  - normandie_sidebar_menu (sidebar_first)
- **Usage**: Called during block placement phase, deletes existing blocks first to avoid conflicts

#### 4. reposition_blocks.php
- **Purpose**: Move existing system blocks to correct regions
- **Output**: Repositions 4 blocks:
  - normandie_main_menu → navigation (weight -20)
  - normandie_messages → content (weight -5)
  - normandie_page_title → header (weight 1)
  - normandie_help → help (weight -10)
- **Usage**: Called after custom block creation to match D7 layout

#### 5. delete_unwanted_blocks.php
- **Purpose**: Delete (not disable) unwanted system blocks
- **Output**: Deletes 6 blocks: account_menu, breadcrumbs, powered, search_form_narrow, search_form_wide, syndicate
- **Usage**: Called after repositioning to clean up interface

### Views Management

#### 6. create_liste_actus_view.php
- **Purpose**: Create liste_actus View with Cycle Slideshow
- **Output**: Creates View entity with 3 displays:
  - default: Slideshow Cycle (views_slideshow_cycle)
  - page: /liste-actus with full pager
  - block: sidebar_second with 3 items
- **Output**: Also creates block placement: normandie_liste_actus_block
- **Usage**: Called BEFORE menu migration to ensure /liste-actus route exists
- **Dependencies**: Requires views_slideshow module

### Entity & Field Management

#### 7. clear_entity_definitions.php
- **Purpose**: Clear cached entity field storage definitions
- **Output**: Deletes entity field definitions from key_value store and clears caches
- **Usage**: Called during truncate phase before regenerating definitions
- **Critical**: Required before field regeneration to avoid stale data

#### 8. regenerate_entity_definitions.php
- **Purpose**: Regenerate entity field storage definitions for all entity types
- **Output**: Saves field definitions for 11 entity types (node, paragraph, taxonomy_term, etc.)
- **Usage**: Called after clearing definitions to ensure fresh field metadata
- **Critical**: Required for proper field loading after truncation

#### 9. install_french_language.php
- **Purpose**: Install French language and set as default
- **Output**: Creates 'fr' language and sets as site default
- **Usage**: Called during truncate phase to match D7 configuration

### Field Hydration & Synchronization

#### 10. hydrate_body_fields.php
- **Purpose**: Hydrate body fields via Entity API setValue()
- **Output**: Loads and saves all body field values using proper API
- **Usage**: Called during post-migration hydration phase
- **Critical**: Required for proper body field rendering

#### 11. sync_paragraph_definitions.php
- **Purpose**: Permanently synchronize paragraph field definitions
- **Output**: Updates EntityDefinitionUpdateManager AND key_value store
- **Usage**: Called after paragraph hydration to prevent cron errors
- **Critical**: Prevents "field definition mismatch" errors

### Validation

#### 12. validate_migration.php
- **Purpose**: Validate migration data integrity
- **Output**: Comprehensive report on:
  - Entity counts (nodes, users, terms, paragraphs)
  - Field population (body fields, paragraph fields)
  - Data integrity (orphan detection)
  - Sample node verification
- **Usage**: Called at end of migration to verify success
- **Critical**: Final verification before production deployment

## Execution Context

All scripts are executed via `drush php:script` which:
- Bootstraps Drupal fully
- Has access to all Drupal APIs
- Runs with web user permissions
- Outputs to stdout (captured by migration.sh)

## Testing Individual Scripts

```bash
cd /path/to/normandie_d10
vendor/bin/drush php:script scripts/utils/create_header_menu.php
vendor/bin/drush php:script scripts/utils/validate_migration.php
```
