<?php

/**
 * Hydrate body fields via setValue() API.
 * 
 * This script loads all body field data from the database
 * and uses the Entity API to properly set values on nodes.
 * Critical for proper field rendering.
 */

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

$db = Database::getConnection();

// Get all body records
$bodies = $db->select("node__body", "b")
  ->fields("b", ["entity_id", "body_value", "body_format"])
  ->execute()
  ->fetchAll();

$total = count($bodies);
$processed = 0;
$errors = 0;

echo "Starting body field hydration for $total nodes...\n";

foreach ($bodies as $body) {
  try {
    $node = Node::load($body->entity_id);
    if ($node) {
      $node->body->setValue([
        "value" => $body->body_value,
        "format" => $body->body_format ?: "full_html",
      ]);
      $node->save();
      $processed++;
    }
  }
  catch (\Exception $e) {
    $errors++;
  }
  
  if (($processed + $errors) % 25 === 0) {
    echo "Progress: $processed/$total\n";
  }
}

echo "✅ Processed: $processed/$total nodes\n";
if ($errors > 0) {
  echo "⚠️ Errors: $errors\n";
}
