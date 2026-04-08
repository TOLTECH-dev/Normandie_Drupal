<?php
/**
 * Create liste_actus View with Cycle Slideshow
 * Requires views_slideshow module
 */

use Drupal\views\Entity\View;
use Drupal\block\Entity\Block;

echo "Creating View: liste_actus with Cycle Slideshow...\n";

// Remove existing
$existing = View::load('liste_actus');
if ($existing) {
  $existing->delete();
  echo "✅ Removed existing liste_actus View\n";
}

// Create View with Cycle Slideshow display (matching D7)
$view = View::create([
  'id' => 'liste_actus',
  'label' => "L'actualité",
  'module' => 'views',
  'description' => 'Slideshow Cycle des actualités - sidebar_second',
  'tag' => '',
  'base_table' => 'node_field_data',
  'base_field' => 'nid',
  'core' => '8.x',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Master',
      'position' => 0,
      'display_options' => [
        'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
        'cache' => ['type' => 'tag', 'options' => []],
        'query' => ['type' => 'views_query', 'options' => []],
        'exposed_form' => ['type' => 'basic', 'options' => []],
        'pager' => ['type' => 'none', 'options' => ['offset' => 0, 'items_per_page' => 3]],
        'style' => [
          'type' => 'slideshow',
          'options' => [
            'slideshow_type' => 'views_slideshow_cycle',
            'slideshow_skin' => 'default',
            'skin_info' => [
              'class' => 'default',
              'name' => 'default',
              'module' => 'views_slideshow',
            ],
            'widgets' => [
              'top' => ['views_slideshow_pager_fields' => ['weight' => 0, 'type' => 'views_slideshow_pager_fields', 'options' => ['views_slideshow_pager_fields_verbosity' => FALSE]]],
              'bottom' => ['views_slideshow_pager_fields' => ['weight' => 0, 'type' => 'views_slideshow_pager_fields', 'options' => ['views_slideshow_pager_fields_verbosity' => FALSE]]],
              'left' => NULL,
              'right' => NULL,
            ],
            'views_slideshow_cycle' => [
              'num_heading_ws' => 0,
              'views_slideshow_cycle_prev_next' => FALSE,
              'views_slideshow_cycle_prev_next_controls_hover' => FALSE,
              'views_slideshow_cycle_pause_on_hover' => FALSE,
              'views_slideshow_cycle_pause' => 5000,
              'views_slideshow_cycle_speed' => 500,
              'views_slideshow_cycle_timeout' => 0,
              'views_slideshow_cycle_nowrap' => 0,
              'views_slideshow_cycle_random' => 0,
              'views_slideshow_cycle_shuffle' => 0,
              'views_slideshow_cycle_advanced' => TRUE,
              'views_slideshow_cycle_advanced_options' => 'easing: "easeInOutQuad"',
            ],
            'asynchronous_rendering' => FALSE,
            'asynchronous_rendering_preserve_sort' => FALSE,
          ],
        ],
        'row' => [
          'type' => 'fields',
          'options' => [
            'inline' => ['title' => 'title', 'body' => 'body', 'view_node' => 'view_node'],
            'separator' => '',
            'hide_empty' => FALSE,
          ],
        ],
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'plugin_id' => 'node_field_title',
            'settings' => ['link_to_entity' => TRUE],
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'body' => [
            'id' => 'body',
            'table' => 'node__body',
            'field' => 'body',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'type' => 'text_summary_or_trimmed',
            'settings' => ['trim_length' => 600],
            'plugin_id' => 'field',
            'click_sort_column' => 'value',
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'view_node' => [
            'id' => 'view_node',
            'table' => 'views',
            'field' => 'view_node',
            'label' => 'en savoir plus',
            'exclude' => FALSE,
            'alter' => ['alter_text' => TRUE, 'text' => '... en savoir plus', 'make_link' => TRUE],
            'element_class' => 'lien-savoir-plus',
            'plugin_id' => 'entity_link',
            'text' => 'View',
            'output_url_as_text' => FALSE,
            'absolute' => FALSE,
          ]
        ],
        'filters' => [
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'operator' => 'in',
            'value' => ['page' => 'page'],
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'bundle',
          ],
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'operator' => '=',
            'value' => '1',
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'boolean',
          ]
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'order' => 'DESC',
            'plugin_id' => 'date',
          ]
        ],
        'header' => [],
        'footer' => [],
        'empty' => [],
        'relationships' => [],
        'arguments' => [],
        'display_extenders' => [],
      ]
    ],
    'page' => [
      'display_plugin' => 'page',
      'id' => 'page',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'display_extenders' => [],
        'path' => 'liste-actus',
        'menu' => ['type' => 'none'],
        'defaults' => [
          'pager' => FALSE,
          'style' => FALSE,
          'row' => FALSE,
          'fields' => FALSE,
          'filters' => FALSE,
          'sorts' => FALSE,
        ],
        'pager' => ['type' => 'full', 'options' => ['offset' => 0, 'items_per_page' => 3]],
        'style' => ['type' => 'default', 'options' => []],
        'row' => [
          'type' => 'fields',
          'options' => [
            'inline' => ['title' => 'title', 'body' => 'body', 'view_node' => 'view_node'],
            'separator' => '',
            'hide_empty' => FALSE,
          ],
        ],
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'plugin_id' => 'node_field_title',
            'settings' => ['link_to_entity' => TRUE],
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'body' => [
            'id' => 'body',
            'table' => 'node__body',
            'field' => 'body',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'type' => 'text_summary_or_trimmed',
            'settings' => ['trim_length' => 600],
            'plugin_id' => 'field',
            'click_sort_column' => 'value',
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'view_node' => [
            'id' => 'view_node',
            'table' => 'views',
            'field' => 'view_node',
            'relationship' => 'none',
            'group_type' => 'group',
            'admin_label' => '',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [
              'alter_text' => FALSE,
              'text' => '',
              'make_link' => FALSE,
            ],
            'element_type' => '',
            'element_class' => '',
            'element_label_type' => '',
            'element_label_class' => '',
            'element_label_colon' => FALSE,
            'element_wrapper_type' => '',
            'element_wrapper_class' => '',
            'element_default_classes' => TRUE,
            'empty' => '',
            'hide_empty' => FALSE,
            'empty_zero' => FALSE,
            'hide_alter_empty' => TRUE,
            'plugin_id' => 'entity_link',
            'text' => 'Lire la suite',
            'output_url_as_text' => FALSE,
            'absolute' => FALSE,
          ]
        ],
        'filters' => [
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'operator' => 'in',
            'value' => ['page' => 'page'],
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'bundle',
          ],
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'operator' => '=',
            'value' => '1',
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'boolean',
          ]
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'order' => 'DESC',
            'plugin_id' => 'date',
          ]
        ],
      ]
    ],
    'block' => [
      'display_plugin' => 'block',
      'id' => 'block',
      'display_title' => 'Block - 3 dernières',
      'position' => 2,
      'display_options' => [
        'display_extenders' => [],
        'block_description' => "L'actualité (3 dernieres)",
        'defaults' => [
          'exposed_form' => FALSE,
          'pager' => FALSE,
          'style' => FALSE,
          'row' => FALSE,
          'fields' => FALSE,
          'filters' => FALSE,
          'sorts' => FALSE,
          'header' => FALSE,
          'footer' => FALSE,
          'empty' => FALSE,
          'relationships' => FALSE,
          'arguments' => FALSE,
          'display_extenders' => FALSE,
        ],
        'exposed_form' => ['type' => 'basic', 'options' => []],
        'pager' => ['type' => 'some', 'options' => ['offset' => 0, 'items_per_page' => 3]],
        'style' => ['type' => 'default', 'options' => []],
        'row' => [
          'type' => 'fields',
          'options' => ['inline' => ['title' => 'title', 'body' => 'body', 'view_node' => 'view_node'], 'separator' => '', 'hide_empty' => FALSE],
        ],
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'plugin_id' => 'node_field_title',
            'settings' => ['link_to_entity' => TRUE],
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'body' => [
            'id' => 'body',
            'table' => 'node__body',
            'field' => 'body',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [],
            'type' => 'text_summary_or_trimmed',
            'settings' => ['trim_length' => 600],
            'plugin_id' => 'field',
            'click_sort_column' => 'value',
            'group_column' => 'value',
            'group_columns' => [],
            'group_rows' => TRUE,
            'delta_limit' => 'unlimited',
            'delta_offset' => 0,
            'delta_reversed' => FALSE,
          ],
          'view_node' => [
            'id' => 'view_node',
            'table' => 'views',
            'field' => 'view_node',
            'relationship' => 'none',
            'group_type' => 'group',
            'admin_label' => '',
            'label' => '',
            'exclude' => FALSE,
            'alter' => [
              'alter_text' => FALSE,
              'text' => '',
              'make_link' => FALSE,
            ],
            'element_type' => '',
            'element_class' => '',
            'element_label_type' => '',
            'element_label_class' => '',
            'element_label_colon' => FALSE,
            'element_wrapper_type' => '',
            'element_wrapper_class' => '',
            'element_default_classes' => TRUE,
            'empty' => '',
            'hide_empty' => FALSE,
            'empty_zero' => FALSE,
            'hide_alter_empty' => TRUE,
            'plugin_id' => 'entity_link',
            'text' => 'Lire la suite',
            'output_url_as_text' => FALSE,
            'absolute' => FALSE,
          ]
        ],
        'filters' => [
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'operator' => 'in',
            'value' => ['page' => 'page'],
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'bundle',
          ],
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'operator' => '=',
            'value' => '1',
            'group' => 1,
            'exposed' => FALSE,
            'plugin_id' => 'boolean',
          ]
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'order' => 'DESC',
            'plugin_id' => 'date',
          ]
        ],
      ]
    ]
  ]
]);

$view->save();
echo "✅ View created with Cycle Slideshow display\n";

// Place block in sidebar_second
echo "Placing block in sidebar_second region...\n";

$existing = Block::load('normandie_liste_actus_block');
if ($existing) {
  $existing->delete();
}

$block = Block::create([
  'id' => 'normandie_liste_actus_block',
  'theme' => 'normandie',
  'region' => 'sidebar_second',
  'weight' => 0,
  'plugin' => 'views_block:liste_actus-block',
  'settings' => [
    'id' => 'views_block:liste_actus-block',
    'label' => "L'actualité",
    'label_display' => 'visible',
    'provider' => 'views',
  ],
  'visibility' => [],
]);

$block->save();
echo "✅ Block placed in sidebar_second\n";

echo "\n✅ Liste Actus View and Block created successfully\n";
