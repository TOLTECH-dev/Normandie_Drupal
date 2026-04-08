<?php

/**
 * Install French language and set as default.
 * 
 * This script installs the French language if not already present
 * and sets it as the default site language.
 */

$language_manager = \Drupal::languageManager();
$languages = $language_manager->getLanguages();

if (!isset($languages['fr'])) {
  $french = \Drupal::service('language.config_factory_override');
  $language = \Drupal::entityTypeManager()
    ->getStorage('configurable_language')
    ->create(['id' => 'fr']);
  $language->save();
  echo "✅ French language installed\n";
  
  // Set as default
  \Drupal::configFactory()->getEditable('system.site')
    ->set('default_langcode', 'fr')
    ->save();
  echo "✅ French set as default language\n";
}
else {
  echo "✅ French language already installed\n";
}
