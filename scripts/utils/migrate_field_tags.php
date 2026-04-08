<?php

/**
 * Script to migrate field_tags from D7 to D10
 * 
 * This script migrates the field_tags (taxonomy references) from the D7 database
 * to the D10 database, ensuring that nodes maintain their tag associations.
 */

use Drupal\node\Entity\Node;

echo "🔄 Starting field_tags migration from D7 to D10...\n\n";

// Get the D10 database connection
$d10_db = \Drupal::database();

// Get environment variables for D7 connection
$db_d7_host = getenv('DB_D7_HOST') ?: 'localhost';
$db_d7_name = getenv('DB_D7_NAME') ?: 'normandie_drupal';
$db_d7_user = getenv('DB_D7_USER') ?: 'root';
$db_d7_password = getenv('DB_D7_PASSWORD') ?: 'root';
$db_d7_port = getenv('DB_D7_PORT') ?: 3306;

// Connect to D7 database directly
try {
  $d7_connection = new \PDO(
    "mysql:host=$db_d7_host;port=$db_d7_port;dbname=$db_d7_name",
    $db_d7_user,
    $db_d7_password
  );
  $d7_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  echo "✅ Connected to D7 database\n";
} catch (\Exception $e) {
  echo "❌ Could not connect to D7 database: " . $e->getMessage() . "\n";
  return;
}

// Fetch all field_tags from D7
try {
  $stmt = $d7_connection->prepare("
    SELECT f.entity_id, f.field_tags_tid, f.delta, n.type
    FROM field_data_field_tags f
    JOIN node n ON f.entity_id = n.nid
    WHERE f.entity_type = 'node' 
      AND f.deleted = 0
      AND n.status = 1
    ORDER BY f.entity_id, f.delta
  ");
  $stmt->execute();
  $d7_tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);
  echo "📊 Found " . count($d7_tags) . " field_tags records in D7\n";
} catch (\Exception $e) {
  echo "❌ Could not fetch D7 data: " . $e->getMessage() . "\n";
  return;
}

if (count($d7_tags) == 0) {
  echo "ℹ️  No field_tags data found in D7\n";
  return;
}

// Check existing count in D10
try {
  $d10_existing = $d10_db->select('node__field_tags', 'nft')
    ->countQuery()
    ->execute()
    ->fetchField();
  echo "📊 Already have {$d10_existing} field_tags records in D10\n\n";
} catch (\Exception $e) {
  echo "⚠️  Could not check existing tags: " . $e->getMessage() . "\n";
  $d10_existing = 0;
}

// Migrate field_tags for each node
$migrated_count = 0;
$skipped_count = 0;
$failed_nodes = [];

echo "🔄 Migrating field_tags...\n";

foreach ($d7_tags as $tag) {
  $nid = $tag['entity_id'];
  $tid = $tag['field_tags_tid'];
  $delta = $tag['delta'];
  $bundle = $tag['type'];

  // Load D10 node
  $node = Node::load($nid);
  
  if (!$node) {
    $skipped_count++;
    continue;
  }

  try {
    // Check if node already has this tag
    $existing = false;
    foreach ($node->get('field_tags') as $existing_tag) {
      if ($existing_tag->target_id == $tid) {
        $existing = true;
        break;
      }
    }

    // Only add if not already present
    if (!$existing) {
      $node->get('field_tags')->appendItem(['target_id' => $tid]);
    }

    // Save the node
    $node->save();
    $migrated_count++;
    echo ".";
    
  } catch (\Exception $e) {
    $failed_nodes[$nid] = $e->getMessage();
    echo "F";
  }
}

echo "\n\n";

// Summary
echo "✅ Migration summary:\n";
echo "   Migrated: $migrated_count nodes\n";
echo "   Skipped (node not found in D10): $skipped_count\n";

if (count($failed_nodes) > 0) {
  echo "❌ Failed: " . count($failed_nodes) . " nodes\n";
  foreach ($failed_nodes as $nid => $error) {
    echo "   - Node $nid: $error\n";
  }
}

// Clear entity cache
try {
  \Drupal::entityTypeManager()->getStorage('node')->resetCache();
  \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  echo "✅ Entity cache cleared\n";
} catch (\Exception $e) {
  echo "⚠️  Could not clear entity cache: " . $e->getMessage() . "\n";
}

echo "\n✅ field_tags migration completed!\n";
