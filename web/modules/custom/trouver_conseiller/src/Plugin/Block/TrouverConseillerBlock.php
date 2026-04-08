<?php

declare(strict_types=1);

namespace Drupal\trouver_conseiller\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\trouver_conseiller\Service\VilleService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Trouver un Conseiller' Block.
 *
 * @Block(
 *   id = "trouver_conseiller_block",
 *   admin_label = @Translation("Block trouver un conseiller"),
 *   category = @Translation("Normandie Custom"),
 * )
 */
class TrouverConseillerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly VilleService $villeService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('trouver_conseiller.ville_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $villes = $this->villeService->getVillesInit();

    return [
      '#theme' => 'trouver_conseiller',
      '#villes' => $villes,
      '#attached' => [
        'library' => [
          'trouver_conseiller/trouver_conseiller',
        ],
      ],
      '#cache' => [
      // Disable cache like D7 DRUPAL_NO_CACHE.
        'max-age' => 0,
      ],
    ];
  }

}
