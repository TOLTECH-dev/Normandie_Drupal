<?php

declare(strict_types=1);

namespace Drupal\cartostructure\Controller;

use Drupal\cartostructure\Service\CartostructureService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CartostructureController extends ControllerBase {

  public function __construct(
    protected readonly CartostructureService $cartostructureService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('cartostructure.cartostructure_service'),
    );
  }

  public function getStructures(): JsonResponse {
    $data = $this->cartostructureService->getStructures();
    return new JsonResponse($data);
  }

  public function getListeStructures(): JsonResponse {
    $data = $this->cartostructureService->getListeStructures();
    return new JsonResponse($data);
  }

  public function getListeDepartement(): JsonResponse {
    $data = $this->cartostructureService->getListeDepartement();
    return new JsonResponse($data);
  }

  public function getListeCodePostal(): JsonResponse {
    $data = $this->cartostructureService->getListeCodePostal();
    return new JsonResponse($data);
  }

  public function getListeVille(): JsonResponse {
    $data = $this->cartostructureService->getListeVille();
    return new JsonResponse($data);
  }

  public function getListeEpci(): JsonResponse {
    $data = $this->cartostructureService->getListeEpci();
    return new JsonResponse($data);
  }

  public function getPermanencesParStructure(string $structure_id): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParStructure($structure_id);
    return new JsonResponse($data);
  }

  public function getPermanencesParDepartement(string $departement): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParDepartement($departement);
    return new JsonResponse($data);
  }

  public function getPermanencesParCodePostal(string $code_postal): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParCodePostal($code_postal);
    return new JsonResponse($data);
  }

  public function getPermanencesParVille(string $ville): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParVille($ville);
    return new JsonResponse($data);
  }

  public function getPermanencesParEpci(int $epci_id): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParEpci($epci_id);
    return new JsonResponse($data);
  }

  public function getPermanencesParStructureEtDepartement(string $structure_id, string $departement): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParStructureEtDepartement($structure_id, $departement);
    return new JsonResponse($data);
  }

  public function getPermanencesParStructureEtCodePostal(string $structure_id, string $code_postal): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParStructureEtCodePostal($structure_id, $code_postal);
    return new JsonResponse($data);
  }

  public function getPermanencesParStructureEtVille(string $structure_id, string $ville): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParStructureEtVille($structure_id, $ville);
    return new JsonResponse($data);
  }

  public function getPermanencesParStructureEtEpci(string $structure_id, int $epci_id): JsonResponse {
    $data = $this->cartostructureService->getPermanencesParStructureEtEpci($structure_id, $epci_id);
    return new JsonResponse($data);
  }

}
