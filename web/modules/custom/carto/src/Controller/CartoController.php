<?php

declare(strict_types=1);

namespace Drupal\carto\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\carto\Service\CartoService;
use Drupal\carto\Service\ExportService;
use Drupal\normandie_core\Validator\NormandieValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

final class CartoController extends ControllerBase {

  public function __construct(
    protected readonly CartoService $cartoService,
    protected readonly ExportService $exportService,
    protected readonly NormandieValidator $validator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('carto.service'),
      $container->get('carto.export_service'),
      $container->get('normandie_core.validator')
    );
  }

  public function getListeDepartementPartenaire(): JsonResponse {
    $data = $this->cartoService->getListeDepartementPartenaire();
    return new JsonResponse($data);
  }

  public function getListeCodePostal(): JsonResponse {
    $data = $this->cartoService->getListeCodePostal();
    return new JsonResponse($data);
  }

  public function getListeVillePartenaire(): JsonResponse {
    $data = $this->cartoService->getListeVillePartenaire();
    return new JsonResponse($data);
  }

  public function getListeEpciPartenaire(): JsonResponse {
    $data = $this->cartoService->getListeEpciPartenaire();
    return new JsonResponse($data);
  }

  public function getPartenaires(): JsonResponse {
    $data = $this->cartoService->getPartenaires();
    return new JsonResponse($data);
  }

  public function getPartenairesParDepartement(string $code): JsonResponse {
    if (!$this->validator->validateDepartement($code)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getPartenairesParDepartement($code);
    return new JsonResponse($data);
  }

  public function getPartenairesParCodePostal(string $code): JsonResponse {
    if (!$this->validator->validateCodePostal($code)) {
      return new JsonResponse(['error' => 'Invalid postal code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getPartenairesParCodePostal($code);
    return new JsonResponse($data);
  }

  public function getPartenairesParVille(string $code): JsonResponse {
    if (!$this->validator->validateCodeInsee($code)) {
      return new JsonResponse(['error' => 'Invalid INSEE code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getPartenairesParVille($code);
    return new JsonResponse($data);
  }

  public function getPartenairesParType(string $type): JsonResponse {
    $data = $this->cartoService->getPartenairesParType($type);
    return new JsonResponse($data);
  }

  public function getPartenairesParTypeCodeDepartement(string $type, string $code): JsonResponse {
    $data = $this->cartoService->getPartenairesParTypeCodeDepartement($type, $code);
    return new JsonResponse($data);
  }

  public function getPartenairesParTypeCodePostal(string $type, string $code): JsonResponse {
    $data = $this->cartoService->getPartenairesParTypeCodePostal($type, $code);
    return new JsonResponse($data);
  }

  public function getPartenairesParTypeVille(string $type, string $code): JsonResponse {
    $data = $this->cartoService->getPartenairesParTypeVille($type, $code);
    return new JsonResponse($data);
  }

  public function getDepartementsMap(): JsonResponse {
    $jsonData = $this->cartoService->getDepartementsMap();
    $data = json_decode($jsonData, TRUE);
    if ($data === NULL) {
      return new JsonResponse(['error' => 'Invalid GeoJSON data'], Response::HTTP_BAD_REQUEST);
    }
    return new JsonResponse($data);
  }

  public function getListeVilleParCodepostalFiltre(string $code): JsonResponse {
    if (!$this->validator->validateCodePostal($code)) {
      return new JsonResponse(['error' => 'Invalid postal code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getListeVilleParCodepostalFiltre($code);
    return new JsonResponse($data);
  }

  public function getListeVilleParCodeDepFiltre(string $code): JsonResponse {
    if (!$this->validator->validateDepartement($code)) {
      return new JsonResponse(['error' => 'Invalid département code'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getListeVilleParCodedepFiltre($code);
    return new JsonResponse($data);
  }

  public function getListePartenairesParEpci(string $epciid): JsonResponse {
    if (!$this->validator->validateEpciId((int) $epciid)) {
      return new JsonResponse(['error' => 'Invalid EPCI ID'], Response::HTTP_BAD_REQUEST);
    }
    $data = $this->cartoService->getListePartenairesParEpci($epciid);
    return new JsonResponse($data);
  }

  public function getListePartenairesParTypeEpci(string $type, string $epciid): JsonResponse {
    $data = $this->cartoService->getListePartenairesParTypeEpci($type, $epciid);
    return new JsonResponse($data);
  }

  public function exportCsv(string $type, Request $request): JsonResponse {
    if (!is_numeric($type) || (int) $type < 0 || (int) $type > 1) {
      return new JsonResponse(['error' => 'Invalid partner type'], Response::HTTP_BAD_REQUEST);
    }

    $params = $request->request->all();
    if (!is_array($params)) {
      return new JsonResponse(['error' => 'Invalid request parameters'], Response::HTTP_BAD_REQUEST);
    }

    try {
      $result = $this->exportService->exportPdf((int) $type, $params);
      return new JsonResponse([
        'pdf' => $result['pdf'],
        'filename' => $result['filename'],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => 'PDF generation failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

}
