<?php

declare(strict_types=1);

namespace Drupal\cartochantier\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cartochantier\Service\CartochantierService;
use Drupal\cartochantier\Constant\DemandeType;
use Drupal\normandie_core\Validator\NormandieValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CartochantierController extends ControllerBase {

  public function __construct(
    protected readonly CartochantierService $service,
    protected readonly NormandieValidator $validator,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('cartochantier.service'),
      $container->get('normandie_core.validator'),
    );
  }

  public function getDemande(): JsonResponse {
    $data = $this->service->getDemande();
    return new JsonResponse($data ?: []);
  }

  public function getTypeDemande(): JsonResponse {
    $data = $this->service->getTypeDemande();
    return new JsonResponse($data ?: []);
  }

  public function getListeDepartement(): JsonResponse {
    $data = $this->service->getListeDepartement();
    return new JsonResponse($data ?: []);
  }

  public function getListeCodePostal(): JsonResponse {
    $data = $this->service->getListeCodePostal();
    return new JsonResponse($data ?: []);
  }

  public function getListeVille(): JsonResponse {
    $data = $this->service->getListeVille();
    return new JsonResponse($data ?: []);
  }

  public function getListeEpci(): JsonResponse {
    $data = $this->service->getListeEpci();
    return new JsonResponse($data ?: []);
  }

  public function getListeVilleParCodePostal(string $code_postal): JsonResponse {
    if (!$this->validator->validateCodePostal($code_postal)) {
      return new JsonResponse(
        ['error' => 'Invalid postal code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getListeVilleParCodePostal($code_postal);
    return new JsonResponse($data ?: []);
  }

  public function getDemandParType(string $type): JsonResponse {
    $typeInt = (int) $type;

    if (!$this->isValidType($typeInt)) {
      return new JsonResponse(
        ['error' => 'Invalid request type'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getDemandParType($typeInt);
    return new JsonResponse($data ?: []);
  }

  public function getListeVilleParDepartement(string $departement): JsonResponse {
    if (!$this->validator->validateDepartement($departement)) {
      return new JsonResponse(
        ['error' => 'Invalid département code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getListeVilleParDepartement($departement);
    return new JsonResponse($data ?: []);
  }

  public function getDemandParCodePostal(string $code_postal): JsonResponse {
    if (!$this->validator->validateCodePostal($code_postal)) {
      return new JsonResponse(
        ['error' => 'Invalid postal code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getDemandParCodePostal($code_postal);
    return new JsonResponse($data ?: []);
  }

  public function getDemandParDepartement(string $departement): JsonResponse {
    if (!$this->validator->validateDepartement($departement)) {
      return new JsonResponse(
        ['error' => 'Invalid département code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getDemandParDepartement($departement);
    return new JsonResponse($data ?: []);
  }

  public function getDemandParVille(string $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(
        ['error' => 'Invalid INSEE code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getDemandParVille($insee);
    return new JsonResponse($data ?: []);
  }

  public function getDemandParEpci(string $epci_id): JsonResponse {
    $epciInt = (int) $epci_id;

    if (!$this->validator->validateEpciId($epciInt)) {
      return new JsonResponse(
        ['error' => 'Invalid EPCI ID'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getDemandParEpci($epciInt);
    return new JsonResponse($data ?: []);
  }

  public function getTypeDemandParDepartement(string $type, string $departement): JsonResponse {
    $typeInt = (int) $type;

    if (!$this->isValidType($typeInt)) {
      return new JsonResponse(
        ['error' => 'Invalid request type'],
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!$this->validator->validateDepartement($departement)) {
      return new JsonResponse(
        ['error' => 'Invalid département code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getTypeDemandParDepartement($typeInt, $departement);
    return new JsonResponse($data ?: []);
  }

  public function getTypeDemandParCodePostal(string $type, string $code_postal): JsonResponse {
    $typeInt = (int) $type;

    if (!$this->isValidType($typeInt)) {
      return new JsonResponse(
        ['error' => 'Invalid request type'],
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!$this->validator->validateCodePostal($code_postal)) {
      return new JsonResponse(
        ['error' => 'Invalid postal code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getTypeDemandParCodePostal($typeInt, $code_postal);
    return new JsonResponse($data ?: []);
  }

  public function getTypeDemandParVille(string $type, string $insee): JsonResponse {
    $typeInt = (int) $type;

    if (!$this->isValidType($typeInt)) {
      return new JsonResponse(
        ['error' => 'Invalid request type'],
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(
        ['error' => 'Invalid INSEE code'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getTypeDemandParVille($typeInt, $insee);
    return new JsonResponse($data ?: []);
  }

  public function getTypeDemandParEpci(string $type, string $epci_id): JsonResponse {
    $typeInt = (int) $type;
    $epciInt = (int) $epci_id;

    if (!$this->isValidType($typeInt)) {
      return new JsonResponse(
        ['error' => 'Invalid request type'],
        Response::HTTP_BAD_REQUEST
      );
    }

    if (!$this->validator->validateEpciId($epciInt)) {
      return new JsonResponse(
        ['error' => 'Invalid EPCI ID'],
        Response::HTTP_BAD_REQUEST
      );
    }

    $data = $this->service->getTypeDemandParEpci($typeInt, $epciInt);
    return new JsonResponse($data ?: []);
  }

  public function getDepartementsMap(): Response {
    $json = $this->service->getDepartementsMap();

    if (!$json) {
      return new JsonResponse(['error' => 'Map data not found'], Response::HTTP_NOT_FOUND);
    }

    return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
  }

  private function isValidType(int $type): bool {
    return in_array($type, DemandeType::VALID_TYPES, TRUE);
  }

}
