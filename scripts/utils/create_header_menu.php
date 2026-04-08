<?php
/**
 * Script to create header-top menu if it doesn't exist
 */

$menu_storage = \Drupal::entityTypeManager()->getStorage('menu');
$menu = $menu_storage->load('header-top');

if (!$menu) {
  $menu = $menu_storage->create([
    'id' => 'header-top',
    'label' => 'Header Top Links',
    'description' => 'Menu for header top logos/branding area',
  ]);
  $menu->save();
  echo "✅ Created header-top menu\n";
} else {
  echo "✅ header-top menu already exists\n";
}
