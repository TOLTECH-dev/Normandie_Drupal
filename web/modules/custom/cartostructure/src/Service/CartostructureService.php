<?php

declare(strict_types=1);

namespace Drupal\cartostructure\Service;

use Drupal\Core\Database\Connection;
use Drupal\normandie_core\Validator\NormandieValidator;
use Psr\Log\LoggerInterface;

final class CartostructureService {

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
    protected readonly NormandieValidator $validator,
  ) {}

  /**
   * Build base query for structure_ permanences.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query object.
   */
  protected function buildStructureQuery() {
    $query = $this->database->select('structure_permanence', 'sp')
      ->fields('sp', [
        'nom',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'jour_ouverture',
        'horaire',
      ])
      ->fields('upc', ['latitude', 'longitude']);
    $query->addExpression("CONCAT('S', s.id)", 'id');
    $query->innerJoin('structure__structure_permanence', 'ssp', 'ssp.structure_permanence_id = sp.id');
    $query->innerJoin('structure_', 's', 's.id = ssp.structure__id');
    $query->innerJoin('structure_statut', 'ss', 's.structure_statut_id = ss.id');
    $query->innerJoin('admin_coordonnee', 'upc', 'sp.id = upc.object_id');
    $query->condition('ss.enabled', 1);
    $query->where('SUBSTR(upc.type, 1, 1) = :type_s', [':type_s' => '1']);

    return $query;
  }

  /**
   * Build base query for EPCI_ permanences.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select query object.
   */
  protected function buildEpciQuery() {
    $query = $this->database->select('EPCI_permanence', 'Ep')
      ->fields('Ep', [
        'nom',
        'adresse',
        'code_postal',
        'ville',
        'telephone',
        'jour_ouverture',
        'horaire',
      ])
      ->fields('upc', ['latitude', 'longitude']);
    $query->addExpression("CONCAT('E', E.id)", 'id');
    $query->innerJoin('EPCI__EPCI_permanence', 'EEp', 'EEp.epci_permanence_id = Ep.id');
    $query->innerJoin('EPCI_', 'E', 'E.id = EEp.epci__id');
    $query->innerJoin('admin_coordonnee', 'upc', 'Ep.id = upc.object_id');
    $query->condition('E.enabled', 1);
    $query->condition('E.point_entree_structure', 1);
    $query->where('SUBSTR(upc.type, 1, 1) = :type_e', [':type_e' => '3']);

    return $query;
  }

  /**
   * Format query results to structure array.
   *
   * @param object $results
   *   Database query results.
   *
   * @return array
   *   Formatted structures array.
   */
  protected function formatStructureResults($results): array {
    $structures = [];
    foreach ($results as $record) {
      $structures[] = [
        'ID_STRUCTURE' => htmlspecialchars($record->id ?? '', ENT_QUOTES, 'UTF-8'),
        'NOM_PERMANENCE' => htmlspecialchars($record->nom ?? '', ENT_QUOTES, 'UTF-8'),
        'ADRESSE' => htmlspecialchars($record->adresse ?? '', ENT_QUOTES, 'UTF-8'),
        'TELEPHONE' => htmlspecialchars($record->telephone ?? '', ENT_QUOTES, 'UTF-8'),
        'CODE_POSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
        'VILLE' => htmlspecialchars($record->ville ?? '', ENT_QUOTES, 'UTF-8'),
        'JOUR_OUVERTURE' => htmlspecialchars($record->jour_ouverture ?? '', ENT_QUOTES, 'UTF-8'),
        'HORAIRE' => htmlspecialchars($record->horaire ?? '', ENT_QUOTES, 'UTF-8'),
        'LAT' => (float) $record->latitude,
        'LONG' => (float) $record->longitude,
      ];
    }
    return $structures;
  }

  public function getStructures(): array {
    $data = [];

    try {
      // Query for structure_ entries.
      $query_s = $this->buildStructureQuery();

      // Query for EPCI_ entries.
      $query_e = $this->buildEpciQuery();

      // Combine with UNION.
      $query_s->union($query_e);

      $results = $query_s->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting structures: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getListeStructures(): array {
    $data = [];

    try {
      // Query for structure_ entries.
      $query_s = $this->database->select('structure_', 's')
        ->fields('si', ['id', 'nom']);
      $query_s->addExpression("CONCAT('S', si.id)", 'id');
      $query_s->innerJoin('structure_statut', 'ss', 's.structure_statut_id = ss.id');
      $query_s->innerJoin('structure_identification', 'si', 's.structure_identification_id = si.id');
      $query_s->condition('ss.enabled', 1);

      // Query for EPCI_ entries.
      $query_e = $this->database->select('EPCI_', 'E')
        ->fields('E', ['id']);
      $query_e->addExpression("CONCAT('E', E.id)", 'id');
      $query_e->addField('E', 'nom_affichage', 'nom');
      $query_e->condition('E.enabled', 1);
      $query_e->condition('E.point_entree_structure', 1);

      // Combine with UNION and order.
      $query_s->union($query_e);
      $query_s->orderBy('nom');

      $results = $query_s->execute();

      $structures = [];
      foreach ($results as $record) {
        $structures[] = [
          'ID_STRUCTURE' => htmlspecialchars($record->id ?? '', ENT_QUOTES, 'UTF-8'),
          'NOM_STRUCTURE' => htmlspecialchars($record->nom ?? '', ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting liste structures: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getListeDepartement(): array {
    $data = [];

    try {
      $query = $this->database->select('up_departement', 'ud')
        ->fields('ud', ['departement_code'])
        ->condition('departement_code', ['14', '27', '50', '61', '76'], 'IN')
        ->orderBy('departement_code', 'ASC');

      $results = $query->execute();

      $departements = [];
      foreach ($results as $record) {
        $departements[] = [
          'CODE_DEPARTEMENT' => htmlspecialchars($record->departement_code ?? '', ENT_QUOTES, 'UTF-8'),
          'NOM_DEPARTEMENT' => htmlspecialchars($record->departement_code ?? '', ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($departements) ? ['departements' => $departements] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting liste departement: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
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

      $data = !empty($codes_postaux) ? ['codes_postaux' => $codes_postaux] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting liste code postal: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getListeVille(): array {
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
          'NOM' => htmlspecialchars($record->nom ?? '', ENT_QUOTES, 'UTF-8'),
          'CODE_POSTAL' => htmlspecialchars($record->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
          'CODE_INSEE' => htmlspecialchars($record->code_insee ?? '', ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($villes) ? ['villes' => $villes] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting liste ville: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getListeEpci(): array {
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
          'ID_EPCI' => $record->id,
          'NOM_EPCI' => htmlspecialchars($record->nom ?? '', ENT_QUOTES, 'UTF-8'),
        ];
      }

      $data = !empty($epcis) ? ['epci' => $epcis] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting liste EPCI: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParStructure(string $identifiantStructure): array {
    $data = [];

    try {
      $validation = $this->validator->validateStructureIdentifier($identifiantStructure);
      if (!$validation['valid']) {
        $this->logger->warning('Invalid structure identifier: @id', ['@id' => $identifiantStructure]);
        return ['error' => 'Invalid structure identifier'];
      }

      $letterStructure = $validation['letter'];
      $structureId = $validation['id'];

      if ($letterStructure === 'S') {
        $query = $this->buildStructureQuery();
        $query->condition('s.id', $structureId);
      }
      elseif ($letterStructure === 'E') {
        $query = $this->buildEpciQuery();
        $query->condition('E.id', $structureId);
      }
      else {
        return ['error' => 'Invalid structure identifier'];
      }

      $results = $query->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par structure: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParDepartement(string $departement): array {
    $data = [];

    try {
      if (!$this->validator->validateDepartement($departement)) {
        $this->logger->warning('Invalid departement code: @code', ['@code' => $departement]);
        return ['error' => 'Invalid departement'];
      }

      // Query for structure_ entries.
      $query_s = $this->buildStructureQuery();
      $query_s->where('SUBSTR(sp.code_postal, 1, 2) = :departement', [':departement' => $departement]);

      // Query for EPCI_ entries.
      $query_e = $this->buildEpciQuery();
      $query_e->where('SUBSTR(Ep.code_postal, 1, 2) = :departement', [':departement' => $departement]);

      // Combine with UNION.
      $query_s->union($query_e);

      $results = $query_s->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par departement: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParCodePostal(string $codePostal): array {
    $data = [];

    try {
      if (!$this->validator->validateCodePostal($codePostal)) {
        $this->logger->warning('Invalid postal code: @code', ['@code' => $codePostal]);
        return ['error' => 'Invalid code postal'];
      }

      // Query for structure_ entries.
      $query_s = $this->buildStructureQuery();
      $query_s->condition('sp.code_postal', $codePostal);

      // Query for EPCI_ entries.
      $query_e = $this->buildEpciQuery();
      $query_e->condition('Ep.code_postal', $codePostal);

      // Combine with UNION.
      $query_s->union($query_e);

      $results = $query_s->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par code postal: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParVille(string $codeInsee): array {
    $data = [];

    try {
      if (!$this->validator->validateCodeInsee($codeInsee)) {
        $this->logger->warning('Invalid INSEE code: @code', ['@code' => $codeInsee]);
        return ['error' => 'Invalid code INSEE'];
      }

      // Query for structure_ entries.
      $query_s = $this->buildStructureQuery();
      $query_s->innerJoin('up_ville', 'sv', 'sv.code_postal = sp.code_postal AND sv.nom = sp.ville');
      $query_s->condition('sv.code_insee', $codeInsee);

      // Query for EPCI_ entries.
      $query_e = $this->buildEpciQuery();
      $query_e->innerJoin('up_ville', 'Ev', 'Ev.code_postal = Ep.code_postal AND Ev.nom = Ep.ville');
      $query_e->condition('Ev.code_insee', $codeInsee);

      // Combine with UNION.
      $query_s->union($query_e);

      $results = $query_s->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par ville: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParEpci(int $epciId): array {
    $data = [];

    try {
      if (!$this->validator->validateEpciId($epciId)) {
        $this->logger->warning('Invalid EPCI ID: @id', ['@id' => $epciId]);
        return ['error' => 'Invalid EPCI ID'];
      }

      // Query for structure_ entries.
      $query_s = $this->buildStructureQuery();
      $query_s->innerJoin('up_ville', 'v', 'v.code_postal = sp.code_postal AND v.nom = sp.ville');
      $query_s->innerJoin('orientation', 'o', 'o.ville_id = v.id');
      $query_s->innerJoin('EPCI_', 'sE', 'sE.id = o.EPCI_id');
      $query_s->condition('sE.id', $epciId);

      // Query for EPCI_ entries.
      $query_e = $this->buildEpciQuery();
      $query_e->innerJoin('up_ville', 'v', 'v.code_postal = Ep.code_postal AND v.nom = Ep.ville');
      $query_e->innerJoin('orientation', 'o', 'o.ville_id = v.id');
      $query_e->innerJoin('EPCI_', 'EE', 'EE.id = o.EPCI_id');
      $query_e->condition('EE.id', $epciId);

      // Combine with UNION.
      $query_s->union($query_e);

      $results = $query_s->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par EPCI: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParStructureEtDepartement(string $identifiantStructure, string $departement): array {
    $data = [];

    try {
      $validation = $this->validator->validateStructureIdentifier($identifiantStructure);
      if (!$validation['valid'] || !$this->validator->validateDepartement($departement)) {
        $this->logger->warning('Invalid parameters: structure=@struct, dept=@dept', [
          '@struct' => $identifiantStructure,
          '@dept' => $departement,
        ]);
        return ['error' => 'Invalid parameters'];
      }

      $letterStructure = $validation['letter'];
      $structureId = $validation['id'];

      if ($letterStructure === 'S') {
        $query = $this->buildStructureQuery();
        $query->where('SUBSTR(sp.code_postal, 1, 2) = :departement', [':departement' => $departement]);
        $query->condition('s.id', $structureId);
      }
      elseif ($letterStructure === 'E') {
        $query = $this->buildEpciQuery();
        $query->where('SUBSTR(Ep.code_postal, 1, 2) = :departement', [':departement' => $departement]);
        $query->condition('E.id', $structureId);
      }
      else {
        return ['error' => 'Invalid structure identifier'];
      }

      $results = $query->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par structure et departement: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParStructureEtCodePostal(string $identifiantStructure, string $codePostal): array {
    $data = [];

    try {
      $validation = $this->validator->validateStructureIdentifier($identifiantStructure);
      if (!$validation['valid'] || !$this->validator->validateCodePostal($codePostal)) {
        $this->logger->warning('Invalid parameters: structure=@struct, cp=@cp', [
          '@struct' => $identifiantStructure,
          '@cp' => $codePostal,
        ]);
        return ['error' => 'Invalid parameters'];
      }

      $letterStructure = $validation['letter'];
      $structureId = $validation['id'];

      if ($letterStructure === 'S') {
        $query = $this->buildStructureQuery();
        $query->condition('sp.code_postal', $codePostal);
        $query->condition('s.id', $structureId);
      }
      elseif ($letterStructure === 'E') {
        $query = $this->buildEpciQuery();
        $query->condition('Ep.code_postal', $codePostal);
        $query->condition('E.id', $structureId);
      }
      else {
        return ['error' => 'Invalid structure identifier'];
      }

      $results = $query->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par structure et code postal: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParStructureEtVille(string $identifiantStructure, string $codeInsee): array {
    $data = [];

    try {
      $validation = $this->validator->validateStructureIdentifier($identifiantStructure);
      if (!$validation['valid'] || !$this->validator->validateCodeInsee($codeInsee)) {
        $this->logger->warning('Invalid parameters: structure=@struct, insee=@insee', [
          '@struct' => $identifiantStructure,
          '@insee' => $codeInsee,
        ]);
        return ['error' => 'Invalid parameters'];
      }

      $letterStructure = $validation['letter'];
      $structureId = $validation['id'];

      if ($letterStructure === 'S') {
        $query = $this->buildStructureQuery();
        $query->innerJoin('up_ville', 'sv', 'sv.code_postal = sp.code_postal AND sv.nom = sp.ville');
        $query->condition('sv.code_insee', $codeInsee);
        $query->condition('s.id', $structureId);
      }
      elseif ($letterStructure === 'E') {
        $query = $this->buildEpciQuery();
        $query->innerJoin('up_ville', 'Ev', 'Ev.code_postal = Ep.code_postal AND Ev.nom = Ep.ville');
        $query->condition('Ev.code_insee', $codeInsee);
        $query->condition('E.id', $structureId);
      }
      else {
        return ['error' => 'Invalid structure identifier'];
      }

      $results = $query->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par structure et ville: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

  public function getPermanencesParStructureEtEpci(string $identifiantStructure, int $epciId): array {
    $data = [];

    try {
      $validation = $this->validator->validateStructureIdentifier($identifiantStructure);
      if (!$validation['valid'] || !$this->validator->validateEpciId($epciId)) {
        $this->logger->warning('Invalid parameters: structure=@struct, epci=@epci', [
          '@struct' => $identifiantStructure,
          '@epci' => $epciId,
        ]);
        return ['error' => 'Invalid parameters'];
      }

      $letterStructure = $validation['letter'];
      $structureId = $validation['id'];

      if ($letterStructure === 'S') {
        $query = $this->buildStructureQuery();
        $query->innerJoin('up_ville', 'v', 'v.code_postal = sp.code_postal AND v.nom = sp.ville');
        $query->innerJoin('orientation', 'o', 'o.ville_id = v.id');
        $query->innerJoin('EPCI_', 'sE', 'sE.id = o.EPCI_id');
        $query->condition('sE.id', $epciId);
        $query->condition('s.id', $structureId);
      }
      elseif ($letterStructure === 'E') {
        $query = $this->buildEpciQuery();
        $query->innerJoin('up_ville', 'v', 'v.code_postal = Ep.code_postal AND v.nom = Ep.ville');
        $query->innerJoin('orientation', 'o', 'o.ville_id = v.id');
        $query->innerJoin('EPCI_', 'EE', 'EE.id = o.EPCI_id');
        $query->condition('EE.id', $epciId);
        $query->condition('E.id', $structureId);
      }
      else {
        return ['error' => 'Invalid structure identifier'];
      }

      $results = $query->execute();
      $structures = $this->formatStructureResults($results);

      $data = !empty($structures) ? ['structures' => $structures] : ['message' => 'no data found'];

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting permanences par structure et EPCI: @error', ['@error' => $e->getMessage()]);
      $data = ['error' => 'Database error'];
    }

    return $data;
  }

}
