<?php

declare(strict_types=1);

namespace Drupal\chiffrescles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\chiffrescles\Service\ChiffresclesService;
use Drupal\normandie_core\Validator\NormandieValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ChiffresclesController extends ControllerBase {

  public function __construct(
    protected readonly ChiffresclesService $chiffresclesService,
    protected readonly NormandieValidator $validator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('chiffrescles.chiffrescles_service'),
      $container->get('normandie_core.validator'),
    );
  }

  public function getNombreDossiers(): JsonResponse {
    $data = $this->chiffresclesService->getNombreDossiers();
    return new JsonResponse($data);
  }

  public function getNombreCheques(): JsonResponse {
    $data = $this->chiffresclesService->getNombreCheques();
    return new JsonResponse($data);
  }

  public function getTypeCheques(): JsonResponse {
    $data = $this->chiffresclesService->getTypeCheques();
    return new JsonResponse($data);
  }

  public function getNombreAuditeurs(): JsonResponse {
    $data = $this->chiffresclesService->getNombreAuditeurs();
    return new JsonResponse($data);
  }

  public function getNombreRenovateurs(): JsonResponse {
    $data = $this->chiffresclesService->getNombreRenovateurs();
    return new JsonResponse($data);
  }

  public function getNombrePermanences(): JsonResponse {
    $data = $this->chiffresclesService->getNombrePermanences();
    return new JsonResponse($data);
  }

  public function getListeDepartement(): JsonResponse {
    $data = $this->chiffresclesService->getListeDepartement();
    return new JsonResponse($data);
  }

  public function getListeVille(): JsonResponse {
    $data = $this->chiffresclesService->getListeVille();
    return new JsonResponse($data);
  }

  public function getListeEpci(): JsonResponse {
    $data = $this->chiffresclesService->getListeEpci();
    return new JsonResponse($data);
  }

  public function getListeVilleParDepartement(int $departement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $departement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getListeVilleParDepartement($departement);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParCodeDepartement(int $codedepartement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParCodeDepartement($codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreChequesParCodeDepartement(int $codedepartement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParCodeDepartement($codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreAuditeursParCodeDepartement(int $codedepartement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreAuditeursParCodeDepartement($codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreRenovateursParCodeDepartement(int $codedepartement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreRenovateursParCodeDepartement($codedepartement);
    return new JsonResponse($data);
  }

  public function getNombrePermanencesParCodeDepartement(int $codedepartement): JsonResponse {
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombrePermanencesParCodeDepartement($codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParVille(int $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParVille($insee);
    return new JsonResponse($data);
  }

  public function getNombreChequesParVille(int $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParVille($insee);
    return new JsonResponse($data);
  }

  public function getNombreAuditeursParVille(int $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreAuditeursParVille($insee);
    return new JsonResponse($data);
  }

  public function getNombreRenovateursParVille(int $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreRenovateursParVille($insee);
    return new JsonResponse($data);
  }

  public function getNombrePermanencesParVille(int $insee): JsonResponse {
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombrePermanencesParVille($insee);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParEpci(int $epciid): JsonResponse {
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getNombreChequesParEpci(int $epciid): JsonResponse {
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getNombreAuditeursParEpci(int $epciid): JsonResponse {
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreAuditeursParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getNombreRenovateursParEpci(int $epciid): JsonResponse {
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreRenovateursParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getNombrePermanencesParEpci(int $epciid): JsonResponse {
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombrePermanencesParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParTypeCheque(int $typecheque): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParTypeCheque($typecheque);
    return new JsonResponse($data);
  }

  public function getNombreChequesParTypeCheque(int $typecheque): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParTypeCheque($typecheque);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParTypeChequeEtEpci(int $typecheque, int $epciid): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParTypeChequeEtEpci($typecheque, $epciid);
    return new JsonResponse($data);
  }

  public function getNombreChequesParTypeChequeEtEpci(int $typecheque, int $epciid): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateEpciId($epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParTypeChequeEtEpci($typecheque, $epciid);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParTypeChequeEtCodeDep(int $typecheque, int $codedepartement): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParTypeChequeEtCodeDep($typecheque, $codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreChequesParTypeChequeEtCodeDep(int $typecheque, int $codedepartement): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateDepartement((string) $codedepartement)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParTypeChequeEtCodeDep($typecheque, $codedepartement);
    return new JsonResponse($data);
  }

  public function getNombreDossiersParTypeChequeEtVille(int $typecheque, int $insee): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreDossiersParTypeChequeEtVille($typecheque, $insee);
    return new JsonResponse($data);
  }

  public function getNombreChequesParTypeChequeEtVille(int $typecheque, int $insee): JsonResponse {
    if (!$this->validator->validateTypeCheque($typecheque)) {
      return new JsonResponse(['error' => 'Invalid type cheque'], Response::HTTP_BAD_REQUEST);
    }
    if (!$this->validator->validateCodeInsee($insee)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->chiffresclesService->getNombreChequesParTypeChequeEtVille($typecheque, $insee);
    return new JsonResponse($data);
  }

}
