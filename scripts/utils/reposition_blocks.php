<?php
/**
 * Reposition blocks to match D7 layout
 */

use Drupal\block\Entity\Block;

echo "Repositioning blocks to match D7 layout...\n";

// Reposition blocks that were created by Drupal but in wrong regions
$repositions = [
  'normandie_main_menu' => ['region' => 'navigation', 'weight' => -20],
  'normandie_messages' => ['region' => 'content', 'weight' => -5],
  'normandie_page_title' => ['region' => 'header', 'weight' => 1],
  'normandie_help' => ['region' => 'help', 'weight' => -10],
];

foreach ($repositions as $block_id => $config) {
  $block = Block::load($block_id);
  if ($block) {
    $block->setRegion($config['region']);
    $block->setWeight($config['weight']);
    $block->save();
    echo "✅ Repositioned {$block_id} to {$config['region']} (weight: {$config['weight']})\n";
  }
}

echo "\n✅ Block repositioning complete\n";
