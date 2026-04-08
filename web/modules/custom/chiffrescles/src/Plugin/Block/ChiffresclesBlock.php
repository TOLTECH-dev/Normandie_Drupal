<?php

declare(strict_types=1);

namespace Drupal\chiffrescles\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a chiffrescles block.
 *
 * @Block(
 *   id = "chiffrescles_block",
 *   admin_label = @Translation("Les chiffres clés"),
 *   category = @Translation("Custom"),
 * )
 */
final class ChiffresclesBlock extends BlockBase {

  public function build(): array {
    return [
      '#theme' => 'chiffrescles',
      '#theme_path' => '/themes/custom/normandie',
      '#title' => $this->t('Les chiffres clés'),
      '#attached' => [
        'library' => [
          'chiffrescles/chiffrescles',
        ],
      ],
    ];
  }

}
