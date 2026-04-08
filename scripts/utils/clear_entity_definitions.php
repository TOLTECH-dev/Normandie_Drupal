<?php

/**
 * Clear cached entity field definitions.
 * 
 * This script clears the entity field storage definitions from key_value store.
 * Critical step before regenerating definitions.
 */

use Drupal\Core\Database\Database;

$db = Database::getConnection();

$deleted = $db->delete('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', '%.field_storage_definitions', 'LIKE')
  ->execute();

echo "✅ Cleared $deleted entity field definitions\n";

// Clear field definition caches
\Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
echo "✅ Field definition cache cleared\n";
