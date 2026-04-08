<?php

/**
 * Permanently synchronize paragraph field definitions.
 * 
 * This is the ONLY way to permanently fix field definition mismatches.
 * Updates BOTH the entity definition manager AND the key_value store.
 * Critical to prevent cron errors about field definition mismatches.
 */

$update_manager = \Drupal::entityDefinitionUpdateManager();
$entity_last_installed = \Drupal::keyValue("entity.definitions.installed");
$field_manager = \Drupal::service("entity_field.manager");

echo "Starting paragraph field definitions synchronization...\n";

// Clear all field caches first
$field_manager->clearCachedFieldDefinitions();

// Get current active definitions from config
$field_definitions = $field_manager->getFieldStorageDefinitions("paragraph");

// Method 1: Update via EntityDefinitionUpdateManager
if (isset($field_definitions["field_titre"])) {
  $update_manager->updateFieldStorageDefinition($field_definitions["field_titre"]);
  echo "✅ field_titre updated via update manager\n";
}

if (isset($field_definitions["field_contenu"])) {
  $update_manager->updateFieldStorageDefinition($field_definitions["field_contenu"]);
  echo "✅ field_contenu updated via update manager\n";
}

// Method 2: Force update the key_value store (this is what Drupal checks during cron)
$installed = $entity_last_installed->get("paragraph.field_storage_definitions");
if ($installed && isset($field_definitions["field_titre"]) && isset($field_definitions["field_contenu"])) {
  // Replace with exact active definitions
  $installed["field_titre"] = $field_definitions["field_titre"];
  $installed["field_contenu"] = $field_definitions["field_contenu"];
  $entity_last_installed->set("paragraph.field_storage_definitions", $installed);
  echo "✅ Key-value store updated with active definitions\n";
}

// Method 3: Clear entity definition cache
\Drupal::service("entity_type.manager")->clearCachedDefinitions();

// Method 4: Clear all related caches
$field_manager->clearCachedFieldDefinitions();

// Verify no changes remain
$changes = $update_manager->getChangeList();
if (empty($changes)) {
  echo "✅ VERIFIED: No definition mismatches remain\n";
}
else {
  echo "⚠️ WARNING: Changes still detected\n";
  print_r($changes);
}
