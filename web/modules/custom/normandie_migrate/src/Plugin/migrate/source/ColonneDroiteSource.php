<?php

namespace Drupal\normandie_migrate\Plugin\migrate\source;

use Drupal\paragraphs\Plugin\migrate\source\d7\FieldCollectionItem;

/**
 * Custom source plugin for colonne_droite field collection items.
 *
 * Extends the native FieldCollectionItem plugin to explicitly expose
 * field_titre and field_contenu fields that are attached to the
 * field collection.
 *
 * @MigrateSource(
 *   id = "colonne_droite_source",
 *   source_module = "field_collection"
 * )
 */
class ColonneDroiteSource extends FieldCollectionItem {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Get parent fields.
    $fields = parent::fields();

    // Add our custom fields explicitly.
    $fields['field_titre'] = $this->t('Title field');
    $fields['field_contenu'] = $this->t('Content field');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field_name' => 'field_colonne_de_droite',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'item_id' => [
        'type' => 'integer',
      ],
    ];
  }

}
