<?php

declare(strict_types=1);

namespace Drupal\cartostructure\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a cartostructure block.
 *
 * @Block(
 *   id = "cartostructure_block",
 *   admin_label = @Translation("Carte des structures"),
 *   category = @Translation("Custom"),
 * )
 */
final class CartostructureBlock extends BlockBase {

  public function build(): array {
    return [
      '#theme' => 'cartostructure',
      '#attached' => [
        'library' => [
          'cartostructure/cartostructure',
        ],
      ],
    ];
  }

}
