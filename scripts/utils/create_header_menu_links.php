<?php
/**
 * Script to create header-top menu links
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

// Check if links already exist
$existing = \Drupal::entityTypeManager()
  ->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'header-top']);

if (count($existing) > 0) {
  echo "✅ header-top menu already has " . count($existing) . " links\n";
  return;
}

// Create "Region Normandie" link
$link1 = MenuLinkContent::create([
  'title' => 'Region Normandie',
  'link' => ['uri' => 'route:<nolink>'],
  'menu_name' => 'header-top',
  'weight' => 0,
  'enabled' => TRUE,
]);
$link1->save();
echo "✅ Created 'Region Normandie' link\n";

// Create "Cheque Eco Energie" link
$link2 = MenuLinkContent::create([
  'title' => 'Cheque Eco Energie',
  'link' => ['uri' => 'route:<nolink>'],
  'menu_name' => 'header-top',
  'weight' => 0,
  'enabled' => TRUE,
]);
$link2->save();
echo "✅ Created 'Cheque Eco Energie' link\n";

echo "✅ header-top menu links created successfully\n";
