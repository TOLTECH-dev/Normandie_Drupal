#!/usr/bin/env php
<?php

// Bootstrap Drupal
define('DRUPAL_ROOT', dirname(__FILE__));

$autoloader = require_once DRUPAL_ROOT . '/vendor/autoload.php';

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;

// Create kernel and boot Drupal
$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals());
$kernel->boot();

$database = Database::getConnection('default');

// Get all body fields from node__body table
$query = $database->select('node__body', 'nb')
  ->fields('nb', ['entity_id', 'body_value', 'body_format'])
  ->orderBy('entity_id', 'ASC');

$results = $query->execute()->fetchAll();

echo "Starting body field reconciliation...\n";
echo "Total records found: " . count($results) . "\n\n";

$count = 0;
$errors = 0;
$success = 0;

foreach ($results as $record) {
  try {
    $node = Node::load($record->entity_id);
    
    if (!$node) {
      echo "✗ Node {$record->entity_id} not found\n";
      $errors++;
      continue;
    }
    
    // Get current body value for comparison
    $current_body = $node->get('body')->value;
    
    // Set body field
    $node->set('body', [
      'value' => $record->body_value,
      'format' => $record->body_format,
    ]);
    
    // Save the node
    $node->save();
    
    $success++;
    $count++;
    
    if ($count % 20 == 0) {
      echo "Progress: {$count}/{" . count($results) . "}\n";
    }
  } catch (\Exception $e) {
    echo "✗ Error processing node {$record->entity_id}: " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "RECONCILIATION COMPLETE\n";
echo str_repeat("=", 50) . "\n";
echo "Successfully updated: {$success}\n";
echo "Errors: {$errors}\n";
echo "Total processed: {$count}\n";

$kernel->terminate(new Request());
