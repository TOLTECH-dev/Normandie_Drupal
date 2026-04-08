<?php

/**
 * Validate migration data integrity.
 * 
 * This script performs comprehensive validation of migrated data:
 * - Entity counts
 * - Field population
 * - Data integrity
 * - Orphan detection
 * - Sample node verification
 */

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

$db = Database::getConnection();

// Counts
$nodes = $db->query("SELECT COUNT(*) FROM node_field_data")->fetchField();
$bodies = $db->query("SELECT COUNT(*) FROM node__body")->fetchField();
$users = $db->query("SELECT COUNT(*) FROM users WHERE uid > 0")->fetchField();
$terms = $db->query("SELECT COUNT(*) FROM taxonomy_term_field_data")->fetchField();
$paragraphs = $db->query("SELECT COUNT(*) FROM paragraphs_item")->fetchField();
$para_refs = $db->query("SELECT COUNT(*) FROM node__field_colonne_de_droite")->fetchField();

echo "\n📊 DATA COUNTS:\n";
echo "   Nodes: $nodes\n";
echo "   Bodies: $bodies\n";
echo "   Users: $users\n";
echo "   Terms: $terms\n";
echo "   Paragraphs: $paragraphs\n";
echo "   Paragraph References: $para_refs\n";

// Paragraph field checks (D10 uses 'paragraph__field_*' not 'paragraphs_item__field_*')
$para_titles = $db->query("SELECT COUNT(DISTINCT entity_id) FROM paragraph__field_titre")->fetchField();
$para_content = $db->query("SELECT COUNT(DISTINCT entity_id) FROM paragraph__field_contenu")->fetchField();

echo "\n📝 PARAGRAPH FIELDS:\n";
echo "   With Titles: $para_titles\n";
echo "   With Content: $para_content\n";

// Orphan check
$orphaned = $db->query("
  SELECT COUNT(*) FROM node__body b 
  WHERE b.entity_id NOT IN (SELECT nid FROM node_field_data)
")->fetchField();

echo "\n🔗 INTEGRITY:\n";
if ($orphaned == 0) {
  echo "   ✅ No orphaned body records\n";
}
else {
  echo "   ⚠️ Found $orphaned orphaned records\n";
}

// Admin check
$admin = $db->query("SELECT uid FROM users WHERE uid = 1")->fetchField();
if ($admin) {
  echo "   ✅ Admin user (uid=1) exists\n";
}
else {
  echo "   ❌ Admin user (uid=1) MISSING\n";
}

// Roles check (D10 uses config entities, not a role table)
try {
  $role_anonymous = Role::load("anonymous");
  $role_authenticated = Role::load("authenticated");
  $role_administrator = Role::load("administrator");
  
  $roles_exist = 0;
  if ($role_anonymous) {
    $roles_exist++;
  }
  if ($role_authenticated) {
    $roles_exist++;
  }
  if ($role_administrator) {
    $roles_exist++;
  }
  
  if ($roles_exist == 3) {
    echo "   ✅ All 3 built-in roles exist\n";
  }
  else {
    echo "   ⚠️ Missing built-in roles ($roles_exist/3)\n";
  }
}
catch (\Exception $e) {
  echo "   ⚠️ Could not verify roles\n";
}

// Paragraph integrity check
$orphaned_paragraphs = $db->query("
  SELECT COUNT(*) FROM paragraphs_item p
  WHERE p.id NOT IN (SELECT DISTINCT field_colonne_de_droite_target_id FROM node__field_colonne_de_droite)
")->fetchField();

if ($orphaned_paragraphs == 0) {
  echo "   ✅ No orphaned paragraphs\n";
}
else {
  echo "   ⚠️ Found $orphaned_paragraphs orphaned paragraphs\n";
}

// Sample node with paragraphs
echo "\n📍 SAMPLE NODE CHECK:\n";
$types_list = "'page', 'cartographie'";
$sample = $db->query(
  "SELECT n.nid, COUNT(r.field_colonne_de_droite_target_id) as para_count
   FROM node_field_data n
   LEFT JOIN node__field_colonne_de_droite r ON n.nid = r.entity_id
   WHERE n.type IN (" . $types_list . ")
   GROUP BY n.nid
   LIMIT 1"
)->fetchObject();

if ($sample) {
  echo "   Node {$sample->nid}: {$sample->para_count} paragraphs\n";
  
  // Check if this node can load paragraphs
  try {
    $node = Node::load($sample->nid);
    $loaded_count = $node->get("field_colonne_de_droite")->count();
    echo "   Via API: $loaded_count paragraphs loaded ✅\n";
  }
  catch (\Exception $e) {
    echo "   Via API: ERROR loading paragraphs\n";
  }
}
