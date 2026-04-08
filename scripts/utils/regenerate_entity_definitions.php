<?php

/**
 * Regenerate entity field storage definitions.
 * 
 * This script clears and regenerates field storage definitions for all entity types.
 * Critical for proper field loading after truncation.
 */

use Drupal\Core\Database\Database;

$entity_field_manager = \Drupal::service('entity_field.manager');
$key_value = \Drupal::keyValue('entity.definitions.installed');

// Entity types to regenerate
$entity_types = [
  'node',
  'taxonomy_term',
  'user',
  'paragraph',
  'comment',
  'block_content',
  'file',
  'menu_link_content',
  'path_alias',
  'shortcut',
  'contact_message'
];

echo "Starting entity field definitions regeneration...\n";

foreach ($entity_types as $entity_type_id) {
  try {
    // Clear cached definitions first
    $entity_field_manager->clearCachedFieldDefinitions();
    
    // Get fresh definitions from code/config
    $field_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
    
    if (!empty($field_definitions)) {
      // Save to key_value
      $key_value->set($entity_type_id . '.field_storage_definitions', $field_definitions);
      echo "✅ Saved $entity_type_id: " . count($field_definitions) . " fields\n";
    }
  }
  catch (Exception $e) {
    echo "⚠️ Skip $entity_type_id: " . $e->getMessage() . "\n";
  }
}

echo "✅ All entity field definitions regenerated\n";
