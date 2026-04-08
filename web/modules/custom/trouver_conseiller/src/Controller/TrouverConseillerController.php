<?php

declare(strict_types=1);

namespace Drupal\trouver_conseiller\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\normandie_core\Validator\NormandieValidator;
use Drupal\trouver_conseiller\Service\VilleService;
use Drupal\trouver_conseiller\Service\CritereService;
use Drupal\trouver_conseiller\Service\ConseillerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for trouver conseiller AJAX endpoints.
 */
class TrouverConseillerController extends ControllerBase {

  private const ERROR_INVALID_PARAMS = 'Paramètres invalides';

  public function __construct(
    protected readonly VilleService $villeService,
    protected readonly CritereService $critereService,
    protected readonly ConseillerService $conseillerService,
    protected readonly NormandieValidator $validator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('trouver_conseiller.ville_service'),
      $container->get('trouver_conseiller.critere_service'),
      $container->get('trouver_conseiller.conseiller_service'),
      $container->get('normandie_core.validator'),
    );
  }

  public function getVilles(Request $request): JsonResponse {
    $cp = $request->query->get('value');

    if (!$this->validator->validatePostalCodePrefix($cp)) {
      return new JsonResponse(['error' => 'Code postal invalide'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $villes = $this->villeService->getVillesByPostalCode($cp);
    $data = !empty($villes)
      ? ['partenaires' => $villes]
      : ['message' => 'no data found'];

    return new JsonResponse($data);
  }

  public function getCritere(Request $request): JsonResponse {
    $nb = (int) $request->query->get('value');

    if (!$this->validator->validateNbPersonnes($nb)) {
      return new JsonResponse(['error' => 'Nombre de personnes invalide'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $plafond = $this->critereService->calculatePlafond($nb);

    return new JsonResponse($plafond);
  }

  public function calculate(Request $request): JsonResponse {
    $villeParam = $request->request->get('ville');
    $revenu = $request->request->get('revenu');
    $error = NULL;

    if (!$villeParam || !$this->validator->validateCodeInsee((int) $villeParam)) {
      $error = self::ERROR_INVALID_PARAMS;
    }
    elseif (!$this->validator->validateRevenueState($revenu)) {
      $error = self::ERROR_INVALID_PARAMS;
    }
    else {
      $ville = (int) $villeParam;
      $villes = $this->villeService->getVillesInit();

      if (!array_key_exists($ville, $villes)) {
        $error = self::ERROR_INVALID_PARAMS;
      }
      else {
        $id_ville = $this->villeService->getVilleIdByCodeInsee($ville);

        if ($id_ville === NULL) {
          $error = self::ERROR_INVALID_PARAMS;
        }
        else {
          $orientations = $this->conseillerService->getOrientations($revenu, $id_ville);

          if (!$this->conseillerService->isSareValid($revenu, $orientations)) {
            return new JsonResponse([
              'success' => 0,
              'error' => 'Votre intercommunalité ne finance pas le Service d\'Accompagnement à la Rénovation Energétique (SARE), par conséquent, aucune suite ne peut être donnée à votre demande.',
              'results' => NULL,
            ]);
          }

          unset($orientations->participation_SARE);

          $structures = $this->conseillerService->loadStructures($orientations);
          $results = $this->conseillerService->formatStructureResults($structures);

          return new JsonResponse([
            'success' => 1,
            'results' => $results,
          ]);
        }
      }
    }

    return new JsonResponse([
      'success' => 0,
      'error' => $error,
      'results' => NULL,
    ], JsonResponse::HTTP_BAD_REQUEST);
  }

}
