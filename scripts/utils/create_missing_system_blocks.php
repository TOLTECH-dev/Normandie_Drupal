<?php
/**
 * Create missing system blocks for normandie theme
 * These blocks are normally created automatically by Drupal but may be missing
 */

use Drupal\block\Entity\Block;

echo "Creating missing system blocks for normandie theme...\n\n";

// List of system blocks that should exist
$system_blocks = [
  // Primary Local Tasks (Login tabs)
  [
    'id' => 'normandie_primary_local_tasks',
    'plugin' => 'local_tasks_block',
    'region' => 'highlighted',
    'weight' => -4,
    'settings' => [
      'id' => 'local_tasks_block',
      'label' => 'Primary tabs',
      'label_display' => '0',
      'provider' => 'core',
      'primary' => true,
      'secondary' => false,
    ],
  ],
  
  // Secondary Local Tasks
  [
    'id' => 'normandie_secondary_local_tasks',
    'plugin' => 'local_tasks_block',
    'region' => 'highlighted',
    'weight' => -3,
    'settings' => [
      'id' => 'local_tasks_block',
      'label' => 'Secondary tabs',
      'label_display' => '0',
      'provider' => 'core',
      'primary' => false,
      'secondary' => true,
    ],
  ],
  
  // Main Content
  [
    'id' => 'normandie_content',
    'plugin' => 'system_main_block',
    'region' => 'content',
    'weight' => 0,
    'settings' => [
      'id' => 'system_main_block',
      'label' => 'Main page content',
      'label_display' => '0',
      'provider' => 'system',
    ],
  ],
  
  // Messages
  [
    'id' => 'normandie_messages',
    'plugin' => 'system_messages_block',
    'region' => 'content',
    'weight' => -5,
    'settings' => [
      'id' => 'system_messages_block',
      'label' => 'Status messages',
      'label_display' => '0',
      'provider' => 'system',
    ],
  ],
  
  // Page Title
  [
    'id' => 'normandie_page_title',
    'plugin' => 'page_title_block',
    'region' => 'header',
    'weight' => 1,
    'settings' => [
      'id' => 'page_title_block',
      'label' => 'Page title',
      'label_display' => '0',
      'provider' => 'core',
    ],
  ],
  
  // Help
  [
    'id' => 'normandie_help',
    'plugin' => 'help_block',
    'region' => 'help',
    'weight' => -10,
    'settings' => [
      'id' => 'help_block',
      'label' => 'Help',
      'label_display' => '0',
      'provider' => 'help',
    ],
  ],
  
  // Primary Admin Actions
  [
    'id' => 'normandie_primary_admin_actions',
    'plugin' => 'local_actions_block',
    'region' => 'content',
    'weight' => -10,
    'settings' => [
      'id' => 'local_actions_block',
      'label' => 'Primary admin actions',
      'label_display' => '0',
      'provider' => 'core',
    ],
  ],
];

$created = 0;
$skipped = 0;

echo "📦 Creating essential system blocks...\n";
foreach ($system_blocks as $block_config) {
  $block_id = $block_config['id'];
  
  // Check if block already exists
  $existing = Block::load($block_id);
  if ($existing) {
    echo "⏭️  Block '{$block_id}' already exists, skipping\n";
    $skipped++;
    continue;
  }
  
  // Create the block
  try {
    Block::create([
      'id' => $block_config['id'],
      'theme' => 'normandie',
      'region' => $block_config['region'],
      'weight' => $block_config['weight'],
      'plugin' => $block_config['plugin'],
      'settings' => $block_config['settings'],
      'visibility' => [],
    ])->save();
    
    echo "✅ Block '{$block_id}' created in region '{$block_config['region']}'\n";
    $created++;
  } catch (\Exception $e) {
    echo "❌ Failed to create block '{$block_id}': " . $e->getMessage() . "\n";
  }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ SUMMARY:\n";
echo "   Created: {$created} blocks\n";
echo "   Skipped: {$skipped} blocks (already exist)\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
echo "🎉 Essential system blocks are in place (branding blocks excluded)!\n";
echo "\n";
