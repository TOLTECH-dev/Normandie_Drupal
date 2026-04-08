<?php

declare(strict_types=1);

namespace Drupal\cartochantier\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a cartochantier block.
 *
 * @Block(
 *   id = "cartochantier_block",
 *   admin_label = @Translation("Cartochantier"),
 *   category = @Translation("Custom"),
 * )
 */
final class CartochantierBlock extends BlockBase {

  public function build(): array {
    return [
      '#theme' => 'cartochantier',
      '#title' => $this->t('Cartochantier'),
      '#attached' => [
        'library' => [
          'cartochantier/cartochantier',
        ],
      ],
    ];
  }

}
