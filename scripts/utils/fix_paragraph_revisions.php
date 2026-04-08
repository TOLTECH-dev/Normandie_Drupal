<?php

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

$db = Database::getConnection();
echo "🔧 Fixing paragraph revision references...\n\n";

$nodes_with_paragraphs = $db->query("SELECT DISTINCT entity_id FROM node__field_colonne_de_droite")->fetchCol();
$total = count($nodes_with_paragraphs);
$fixed = 0;

echo "Found $total nodes with paragraphs\n\n";

foreach ($nodes_with_paragraphs as $nid) {
  try {
    $node = Node::load($nid);
    if (!$node || !$node->hasField('field_colonne_de_droite')) {
      continue;
    }
    
    $field_values = $node->get('field_colonne_de_droite')->getValue();
    $needs_fix = false;
    $corrected_values = [];
    
    foreach ($field_values as $value) {
      $target_id = $value['target_id'];
      $current_revision_id = $value['target_revision_id'];
      
      $real_revision_id = $db->query("SELECT revision_id FROM {paragraphs_item} WHERE id = :id", [':id' => $target_id])->fetchField();
      
      if ($real_revision_id && $real_revision_id != $current_revision_id) {
        $needs_fix = true;
        echo "  Node $nid: Para $target_id - fixing revision $current_revision_id → $real_revision_id\n";
      }
      
      $corrected_values[] = [
        'target_id' => $target_id,
        'target_revision_id' => $real_revision_id ?: $current_revision_id,
      ];
    }
    
    if ($needs_fix) {
      $node->set('field_colonne_de_droite', $corrected_values);
      $node->save();
      $fixed++;
    }
  }
  catch (Exception $e) {
    echo "  ❌ Error on node $nid: " . $e->getMessage() . "\n";
  }
}

echo "\n✅ Fixed: $fixed nodes\n";
echo "🎉 Done!\n";