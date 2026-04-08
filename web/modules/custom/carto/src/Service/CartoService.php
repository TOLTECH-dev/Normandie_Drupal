<?php

declare(strict_types=1);

namespace Drupal\carto\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\normandie_core\Validator\NormandieValidator;
use Psr\Log\LoggerInterface;

final class CartoService {

  public function __construct(
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
    protected readonly ExtensionPathResolver $extensionPathResolver,
    protected readonly NormandieValidator $validator,
  ) {}

  protected function buildPartnersQuery() {
    $query = $this->database->select('partenaire_', 'p')
      ->fields('pag', [
        'adresse',
        'nom',
        'telephone',
        'code_postal',
        'ville',
        'email',
      ])
      ->fields('pad', ['site_internet', 'complement'])
      ->fields('upc', ['latitude', 'longitude']);

    $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');
    $query->addExpression("SUBSTR(p.type, 4)", 'labeltype');
    $query->addExpression("pr.complement", 'complement_identification');

    $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
    $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
    $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
    $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
    $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
    $query->innerJoin('admin_coordonnee', 'upc', 'pag.id = upc.object_id');
    $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');

    $query->condition('ps.enabled', 1);
    $query->where('SUBSTR(upc.type, 1, 1) = :upc_type', [':upc_type' => '0']);

    return $query;
  }

  protected function formatPartnersResults(object $results): array {
    $partners = [];
    foreach ($results as $record) {
      $partners[] = [
        'ADRESSE' => !empty($record->adresse) ? htmlspecialchars($record->adresse, ENT_QUOTES, 'UTF-8') : NULL,
        'RAISONSOCIALE' => !empty($record->nom) ? htmlspecialchars($record->nom, ENT_QUOTES, 'UTF-8') : NULL,
        'TELEPHONE' => !empty($record->telephone) ? htmlspecialchars($record->telephone, ENT_QUOTES, 'UTF-8') : NULL,
        'THEMA_PRESTA' => !empty($record->type) ? htmlspecialchars($record->type, ENT_QUOTES, 'UTF-8') : NULL,
        'THEMA_PRESTA_LABEL' => !empty($record->labeltype) ? htmlspecialchars(ucfirst(ltrim($record->labeltype)), ENT_QUOTES, 'UTF-8') : NULL,
        'CODE_POSTAL' => !empty($record->code_postal) ? htmlspecialchars($record->code_postal, ENT_QUOTES, 'UTF-8') : NULL,
        'VILLE' => !empty($record->ville) ? htmlspecialchars($record->ville, ENT_QUOTES, 'UTF-8') : NULL,
        'EMAIL' => !empty($record->email) ? htmlspecialchars($record->email, ENT_QUOTES, 'UTF-8') : NULL,
        'WWW' => !empty($record->site_internet) ? htmlspecialchars($record->site_internet, ENT_QUOTES, 'UTF-8') : NULL,
        'COMPLEMENT' => !empty($record->complement) ? htmlspecialchars($record->complement, ENT_QUOTES, 'UTF-8') : NULL,
        'COMPLEMENT_IDENTIFICATION' => !empty($record->complement_identification) ? htmlspecialchars($record->complement_identification, ENT_QUOTES, 'UTF-8') : NULL,
        'LAT' => !empty($record->latitude) ? (string) $record->latitude : NULL,
        'LONG' => !empty($record->longitude) ? (string) $record->longitude : NULL,
      ];
    }
    return $partners;
  }

  public function getListeDepartementPartenaire(): array {
    $data = [];

    try {
      $query = $this->database->select('up_departement', 'ud')
        ->fields('ud', ['departement_code'])
        ->condition('departement_code', ['14', '27', '50', '61', '76'], 'IN')
        ->orderBy('departement_code', 'ASC');

      $results = $query->execute();

      $departements = [];
      foreach ($results as $record) {
        $departements[] = ['CODE_DEPARTEMENT' => htmlspecialchars($record->departement_code ?? '', ENT_QUOTES, 'UTF-8')];
      }

      $data = !empty($departements) ? ['partenaires' => $departements] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching departments: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListeCodePostal(): array {
    $data = [];

    try {
      $or = $this->database->condition('OR');
      $or->condition('code_postal', '14%', 'LIKE');
      $or->condition('code_postal', '27%', 'LIKE');
      $or->condition('code_postal', '50%', 'LIKE');
      $or->condition('code_postal', '61%', 'LIKE');
      $or->condition('code_postal', '76%', 'LIKE');

      $query = $this->database->select('up_ville', 'uv')
        ->fields('uv', ['code_postal'])
        ->distinct()
        ->condition($or)
        ->orderBy('code_postal');

      $results = $query->execute();

      $codes_postaux = [];
      foreach ($results as $record) {
        $codes_postaux[] = ['CODE_POSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8')];
      }

      $data = !empty($codes_postaux) ? ['partenaires' => $codes_postaux] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching postal codes: @message', ['@message' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getListeVillePartenaire(): array {
    $data = [];

    try {
      $or = $this->database->condition('OR');
      $or->condition('code_postal', '14%', 'LIKE');
      $or->condition('code_postal', '27%', 'LIKE');
      $or->condition('code_postal', '50%', 'LIKE');
      $or->condition('code_postal', '61%', 'LIKE');
      $or->condition('code_postal', '76%', 'LIKE');

      $query = $this->database->select('up_ville', 'uv')
        ->fields('uv', ['nom', 'code_postal', 'code_insee'])
        ->distinct()
        ->condition($or)
        ->orderBy('nom')
        ->orderBy('code_postal');

      $results = $query->execute();

      $villes = [];
      foreach ($results as $record) {
        $villes[] = [
          'CODEINSEE' => htmlspecialchars($record->code_insee ?? '', ENT_QUOTES, 'UTF-8'),
          'CODEPOSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
          'VILLE' => htmlspecialchars("{$record->nom} ( {$record->code_postal} )", ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($villes) ? ['partenaires' => $villes] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching towns: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListeEpciPartenaire(): array {
    $data = [];

    try {
      $query = $this->database->select('EPCI_', 'e')
        ->fields('e', ['id', 'nom']);
      $query->where('SUBSTR(e.code_postal, 1, 2) IN (:depts[])', [
        ':depts[]' => ['14', '27', '50', '61', '76'],
      ]);
      $query->condition('e.enabled', 1);
      $query->orderBy('e.nom');

      $results = $query->execute();

      $epcis = [];
      foreach ($results as $record) {
        $epcis[] = [
          'epci' => htmlspecialchars($record->nom ?? '', ENT_QUOTES, 'UTF-8'),
          'EPCI_ID' => (int) $record->id,
        ];
      }

      $data = !empty($epcis) ? ['epci' => $epcis] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching EPCI: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenaires(): array {
    $data = [];

    try {
      $query = $this->buildPartnersQuery();
      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParDepartement(string $codeDepartement): array {
    $data = [];

    if (!$this->validator->validateDepartement($codeDepartement)) {
      $this->logger->warning('Invalid department code: @code', ['@code' => $codeDepartement]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(pag.code_postal, 1, 2) = :departement', [':departement' => $codeDepartement]);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by department: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParCodePostal(string $codePostal): array {
    $data = [];

    if (!$this->validator->validateCodePostal($codePostal)) {
      $this->logger->warning('Invalid postal code: @code', ['@code' => $codePostal]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->condition('pag.code_postal', $codePostal);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by postal code: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParVille(string $codeInsee): array {
    $data = [];

    if (!$this->validator->validateCodeInsee($codeInsee)) {
      $this->logger->warning('Invalid city code: @code', ['@code' => $codeInsee]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->condition('upv.code_insee', $codeInsee);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by city: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParType(string $type): array {
    $data = [];

    if (!is_numeric($type)) {
      $this->logger->warning('Invalid partner type: @type', ['@type' => $type]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(p.type, 1, 1) = :type', [':type' => $type]);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by type: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParTypeCodeDepartement(string $type, string $codeDepartement): array {
    $data = [];

    if (!is_numeric($type) || !$this->validator->validateDepartement($codeDepartement)) {
      $this->logger->warning('Invalid parameters: type=@type, dept=@dept', [
        '@type' => $type,
        '@dept' => $codeDepartement,
      ]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(p.type, 1, 1) = :type', [':type' => $type]);
      $query->where('SUBSTR(pag.code_postal, 1, 2) = :departement', [':departement' => $codeDepartement]);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = ['partenaires' => $partners];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by type and department: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParTypeCodePostal(string $type, string $codePostal): array {
    $data = [];

    if (!is_numeric($type) || !$this->validator->validateCodePostal($codePostal)) {
      $this->logger->warning('Invalid parameters: type=@type, cp=@cp', ['@type' => $type, '@cp' => $codePostal]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(p.type, 1, 1) = :type', [':type' => $type]);
      $query->condition('pag.code_postal', $codePostal);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by type and postal code: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getPartenairesParTypeVille(string $type, string $codeInsee): array {
    $data = [];

    if (!is_numeric($type) || !$this->validator->validateCodeInsee($codeInsee)) {
      $this->logger->warning('Invalid parameters: type=@type, insee=@insee', ['@type' => $type, '@insee' => $codeInsee]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(p.type, 1, 1) = :type', [':type' => $type]);
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->condition('upv.code_insee', $codeInsee);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by type and city: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListePartenairesParEpci(string $epciId): array {
    $data = [];

    if (!is_numeric($epciId)) {
      $this->logger->warning('Invalid EPCI ID: @id', ['@id' => $epciId]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = upv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->condition('e.id', $epciId);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by EPCI: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListePartenairesParTypeEpci(string $type, string $epciId): array {
    $data = [];

    if (!is_numeric($type) || !is_numeric($epciId)) {
      $this->logger->warning('Invalid parameters: type=@type, epci_id=@epci_id', [
        '@type' => $type,
        '@epci_id' => $epciId,
      ]);
      return [];
    }

    try {
      $query = $this->buildPartnersQuery();
      $query->where('SUBSTR(p.type, 1, 1) = :type', [':type' => $type]);
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = upv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->condition('e.id', $epciId);

      $results = $query->execute();
      $partners = $this->formatPartnersResults($results);

      $data = !empty($partners) ? ['partenaires' => $partners] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching partners by type and EPCI: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListeVilleParCodepostalFiltre(string $codePostal): array {
    $data = [];

    if (!$this->validator->validateCodePostal($codePostal)) {
      $this->logger->warning('Invalid postal code: @code', ['@code' => $codePostal]);
      return ['message' => 'INJECTION SQL'];
    }

    try {
      $query = $this->database->select('up_ville', 'uv')
        ->fields('uv', ['nom', 'code_postal', 'code_insee'])
        ->distinct()
        ->condition('uv.code_postal', $codePostal)
        ->orderBy('uv.nom');

      $results = $query->execute();

      $villes = [];
      foreach ($results as $record) {
        $villes[] = [
          'CODEINSEE' => htmlspecialchars($record->code_insee ?? '', ENT_QUOTES, 'UTF-8'),
          'CODE_POSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
          'VILLE' => htmlspecialchars("{$record->nom} ( {$record->code_postal} )", ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($villes) ? ['partenaires' => $villes] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching cities by postal code: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getListeVilleParCodedepFiltre(string $codeDepartement): array {
    $data = [];

    if (!$this->validator->validateDepartement($codeDepartement)) {
      $this->logger->warning('Invalid department code: @code', ['@code' => $codeDepartement]);
      return ['message' => 'INJECTION SQL'];
    }

    try {
      $query = $this->database->select('up_ville', 'uv')
        ->fields('uv', ['nom', 'code_postal', 'code_insee'])
        ->distinct()
        ->where('uv.code_postal LIKE :dept', [':dept' => "{$codeDepartement}%"])
        ->orderBy('uv.nom');

      $results = $query->execute();

      $villes = [];
      foreach ($results as $record) {
        $villes[] = [
          'CODEINSEE' => htmlspecialchars($record->code_insee ?? '', ENT_QUOTES, 'UTF-8'),
          'CODE_POSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
          'VILLE' => htmlspecialchars("{$record->nom} ( {$record->code_postal} )", ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($villes) ? ['partenaires' => $villes] : ['message' => 'no data found'];
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching cities by department: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    return $data;
  }

  public function getDepartementsMap(): string {
    $module_path = $this->extensionPathResolver->getPath('module', 'carto');
    $json_file = $module_path . '/json/departements-normandie.json';

    if (file_exists($json_file)) {
      $content = file_get_contents($json_file);
      return $content !== FALSE ? $content : json_encode(['message' => 'Map data not found']);
    }

    return json_encode(['message' => 'Map data not found']);
  }

}
