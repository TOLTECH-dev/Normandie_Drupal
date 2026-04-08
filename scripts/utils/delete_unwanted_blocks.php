<?php
/**
 * Delete unwanted default blocks
 */

use Drupal\block\Entity\Block;

echo "Deleting unwanted default blocks...\n";

// Blocks to delete (not needed in normandie theme)
$blocks_to_delete = [
  'normandie_account_menu',
  'normandie_breadcrumbs',
  'normandie_powered',
  'normandie_search_form_narrow',
  'normandie_search_form_wide',
  'normandie_syndicate',
];

foreach ($blocks_to_delete as $block_id) {
  $block = Block::load($block_id);
  if ($block) {
    $block->delete();
    echo "✅ Deleted {$block_id}\n";
  }
}

echo "\n✅ Unwanted blocks deleted\n";
