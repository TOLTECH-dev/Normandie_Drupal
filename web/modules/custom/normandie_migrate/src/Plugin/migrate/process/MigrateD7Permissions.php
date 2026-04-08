<?php

namespace Drupal\normandie_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Migrates D7 role permissions to D10 during user role migration.
 *
 * This plugin is called after the role is created in D10 to populate
 * its permissions from the D7 source.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_d7_permissions"
 * )
 */
class MigrateD7Permissions extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // $value is the rid from source
    $rid = $value;
    
    return $this->getPermissionsForRole($rid);
  }

  /**
   * Get permissions for a specific role
   */
  protected function getPermissionsForRole($rid) {
    
    // Get D7 database connection
    try {
      $d7_db = Database::getConnection('default', 'd7_source');
    } catch (\Exception $e) {
      // If D7 source doesn't exist, use the default connection with d7 prefix
      $d7_db = Database::getConnection('default', 'default');
    }
    
    // Query D7 permissions for this role
    $query = $d7_db->select('role_permission', 'rp')
      ->fields('rp', ['permission'])
      ->condition('rp.rid', $rid);
    
    $d7_permissions = $query->execute()->fetchCol();
    
    // Check if user can edit content in D7 BEFORE mapping
    $can_edit_content = in_array('edit own page content', $d7_permissions) || 
                       in_array('edit any page content', $d7_permissions);
    
    \Drupal::logger('normandie_migrate')->notice('MigrateD7Permissions - RID: @rid, Can edit content: @can_edit, D7 perms: @perms', [
      '@rid' => $rid,
      '@can_edit' => $can_edit_content ? 'YES' : 'NO',
      '@perms' => implode(', ', $d7_permissions),
    ]);
    
    // Map D7 permissions to D10 permissions
    $d10_permissions = [];
    foreach ($d7_permissions as $permission) {
      $mapped = $this->mapPermission($permission);
      if ($mapped) {
        $d10_permissions[] = $mapped;
      }
    }
    
    // D10-specific permissions mapping:
    // If user can edit content in D7, they need media library access in D10
    // (this permission didn't exist in D7 but is required for the same functionality)
    if ($can_edit_content) {
      // Media library access is required for users who can edit content
      $d10_permissions[] = 'access media library';
      \Drupal::logger('normandie_migrate')->notice('MigrateD7Permissions - Added access media library for RID: @rid', [
        '@rid' => $rid,
      ]);
    }
    
    $final_perms = array_unique($d10_permissions);
    \Drupal::logger('normandie_migrate')->notice('MigrateD7Permissions - Final D10 perms for RID @rid: @final', [
      '@rid' => $rid,
      '@final' => implode(', ', $final_perms),
    ]);
    
    // Return the mapped permissions
    return $final_perms;
  }

  /**
   * Map D7 permission names to D10 permission names.
   */
  protected function mapPermission($d7_permission) {
    $map = [
      'create page content' => 'create page content',
      'edit own page content' => 'edit own page content',
      'edit any page content' => 'edit any page content',
      'delete own page content' => 'delete own page content',
      'delete any page content' => 'delete any page content',
      'create cartographie content' => 'create cartographie content',
      'edit own cartographie content' => 'edit own cartographie content',
      'edit any cartographie content' => 'edit any cartographie content',
      'delete own cartographie content' => 'delete own cartographie content',
      'delete any cartographie content' => 'delete any cartographie content',
      'access content' => 'access content',
      'access content overview' => 'access content overview',
      'access administration pages' => 'access administration pages',
      'access site in maintenance mode' => 'access site in maintenance mode',
      'access toolbar' => 'access toolbar',
      'access dashboard' => 'access dashboard',
      'view the administration theme' => 'view the administration theme',
      'administer taxonomy' => 'administer taxonomy',
      'delete terms in 1' => 'delete terms in actus',
      'edit terms in 1' => 'edit terms in actus',
      'delete terms in 2' => 'delete terms in tags',
      'edit terms in 2' => 'edit terms in tags',
      'administer menu' => 'administer menu',
      'administer menu attributes' => 'administer menu',
      'administer url aliases' => 'administer url aliases',
      'create url aliases' => 'create url aliases',
      'administer blocks' => 'administer blocks',
      'administer languages' => 'administer languages',
      'translate interface' => 'translate interface',
      'use text format filtered_html' => 'use text format basic_html',
      'use text format full_html' => 'use text format full_html',
      'view files' => 'view media',
      'access comments' => 'access comments',
      'post comments' => 'post comments',
      'skip comment approval' => 'skip comment approval',
      'search content' => 'search content',
      'access site-wide contact form' => 'access site-wide contact form',
      'access site reports' => 'access site reports',
      'access library reports' => 'access site reports',
      // 'block IP addresses' in D7 is an admin-only permission in D10
      // Do NOT map to 'administer users' for non-admin roles
      'block IP addresses' => NULL,
      'access shortcuts' => 'access shortcuts',
    ];
    
    return $map[$d7_permission] ?? NULL;
  }

}
