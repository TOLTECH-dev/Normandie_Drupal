<?php

declare(strict_types=1);

namespace Drupal\carto\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\carto\Service\CartoService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Carto Map' Block.
 *
 * Detects auditeur (Node 68) vs rénovateur (Node 69) context
 * and displays appropriate partner mapping data.
 *
 * @Block(
 *   id = "carto_block",
 *   admin_label = @Translation("Carto des partenaires"),
 *   category = @Translation("Normandie"),
 * )
 */
final class CartoMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly CartoService $cartoService,
    protected readonly RouteMatchInterface $routeMatch,
    protected readonly ConfigFactoryInterface $configFactory,
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
      $container->get('carto.service'),
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Respects D7 logic: detects auditeur vs rénovateur based on current node.
   * - Node 68 = Auditeurs (type=0, isAuditeur=1)
   * - Node 69 = Rénovateurs (type=1, isAuditeur=0)
   */
  public function build(): array {
    $is_auditeur = 0;

    // Detect context based on current node.
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof Node) {
      $carto_config = $this->configFactory->get('carto.settings');
      $carto_nodes = $carto_config->get('nodes');

      $current_nid = $node->id();
      $auditeur_nid = $carto_nodes['auditeur'] ?? 68;

      // If on auditeur node (68), set flag to 1.
      // If on renovateur node (69), flag stays 0.
      $is_auditeur = ($current_nid == $auditeur_nid) ? 1 : 0;
    }

    return [
      '#theme' => 'carto',
      '#title' => $this->t('Cartographie'),
      '#isAuditeur' => $is_auditeur,
      '#attached' => [
        'library' => [
          'carto/carto_lib',
        ],
      ],
    ];
  }

}
