<?php
/**
 * Create custom blocks for normandie theme
 * Matches D7 block configuration
 */

use Drupal\block\Entity\Block;

echo "Creating custom blocks for normandie theme...\n";

// Delete existing custom blocks to avoid conflicts
$blocks_to_delete = [
  'normandie_addtoany',
  'normandie_footer_menu',
  'normandie_logo_block',
  'normandie_main_menu',
  'normandie_secondary_menu',
  'normandie_sidebar_menu',
];

foreach ($blocks_to_delete as $block_id) {
  $existing = Block::load($block_id);
  if ($existing) {
    $existing->delete();
  }
}

echo "✅ Cleaned existing blocks\n";

// 1. AddToAny social sharing block
Block::create([
  'id' => 'normandie_addtoany',
  'theme' => 'normandie',
  'region' => 'navigation_top',
  'weight' => -19,
  'plugin' => 'addtoany_block',
  'settings' => [
    'id' => 'addtoany_block',
    'label' => 'AddToAny',
    'label_display' => '0',
    'provider' => 'addtoany',
  ],
  'visibility' => [],
])->save();
echo "✅ AddToAny block created\n";

// 2. Footer menu block
Block::create([
  'id' => 'normandie_footer_menu',
  'theme' => 'normandie',
  'region' => 'footer',
  'weight' => 0,
  'plugin' => 'system_menu_block:menu-menu-footer',
  'settings' => [
    'id' => 'system_menu_block:menu-menu-footer',
    'label' => 'Menu footer',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 1,
    'depth' => 0,
    'expand_all_items' => FALSE,
  ],
  'visibility' => [],
])->save();
echo "✅ Footer menu block created\n";

// 3. Logo/Header top menu block (navigation_before_top)
Block::create([
  'id' => 'normandie_logo_block',
  'theme' => 'normandie',
  'region' => 'navigation_before_top',
  'weight' => -18,
  'plugin' => 'system_menu_block:header-top',
  'settings' => [
    'id' => 'system_menu_block:header-top',
    'label' => 'Header Logos (Branding)',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 1,
    'depth' => 0,
    'expand_all_items' => FALSE,
  ],
  'visibility' => [],
])->save();
echo "✅ Logo/Header block created\n";

// 4. Secondary menu (subnavigation)
Block::create([
  'id' => 'normandie_secondary_menu',
  'theme' => 'normandie',
  'region' => 'subnavigation',
  'weight' => -19,
  'plugin' => 'system_menu_block:menu-normandie-navigation',
  'settings' => [
    'id' => 'system_menu_block:menu-normandie-navigation',
    'label' => 'Normandie Navigation',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 1,
    'depth' => 0,
    'expand_all_items' => FALSE,
  ],
  'visibility' => [],
])->save();
echo "✅ Secondary menu block created\n";

// 5. Main Navigation block (Navigation Principale) - MISSING FROM ORIGINAL SCRIPT!
// D7: menu_block:1 in 'navigation' region displaying main-menu
Block::create([
  'id' => 'normandie_main_menu',
  'theme' => 'normandie',
  'region' => 'navigation',
  'weight' => -20,
  'plugin' => 'system_menu_block:main',
  'settings' => [
    'id' => 'system_menu_block:main',
    'label' => 'Navigation principale',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 1,
    'depth' => 1,
    'expand_all_items' => TRUE,
  ],
  'visibility' => [],
])->save();
echo "✅ Main Navigation block created\n";

// 6. Sidebar menu
Block::create([
  'id' => 'normandie_sidebar_menu',
  'theme' => 'normandie',
  'region' => 'sidebar_first',
  'weight' => -20,
  'plugin' => 'system_menu_block:main',
  'settings' => [
    'id' => 'system_menu_block:main',
    'label' => 'Sidebar menu',
    'label_display' => '0',
    'provider' => 'system',
    'level' => 2,
    'depth' => 0,
    'expand_all_items' => TRUE,
  ],
  'visibility' => [],
])->save();
echo "✅ Sidebar menu block created\n";

echo "\n✅ All custom blocks created successfully\n";
