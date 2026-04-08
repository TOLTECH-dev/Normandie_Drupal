<?php

declare(strict_types=1);

namespace Drupal\trouver_conseiller\Service;

use Drupal\Core\Database\Connection;
use Drupal\normandie_core\Validator\NormandieValidator;
use Psr\Log\LoggerInterface;

final class ConseillerService {

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
    protected readonly NormandieValidator $validator,
  ) {}

  public function getOrientations(string $state, int $villeId): ?object {
    if (!$this->validator->validateRevenueState($state) || !$this->validator->validateVilleId($villeId)) {
      $this->logger->warning('Invalid parameters: state=@state, ville=@ville', [
        '@state' => $state,
        '@ville' => $villeId,
      ]);
      return NULL;
    }

    try {
      $query = $this->database->select('up_ville', 'up');
      $query->leftJoin('orientation', 'ori', 'up.id=ori.ville_id');
      $query->leftJoin('EPCI_', 'E', 'E.id=ori.EPCI_id');
      $query->addField('ori', 'id', 'ori_id');
      $query->addField('E', 'id', 'EPCI_id');
      $query->addField('E', 'participation_SARE');
      $query->addField('E', 'point_entree_structure');
      $query->condition('ori.ville_id', $villeId, '=');

      $joinTable = match ($state) {
        'inf' => 'orientation_structure_inferieur',
        'sup' => 'orientation_structure_superieur',
        default => throw new \InvalidArgumentException("Invalid state: $state"),
      };

      $alias = ($state === 'inf') ? 'osi' : 'oss';
      $query->leftJoin($joinTable, $alias, "$alias.orientation_id=ori.id");
      $query->addExpression("GROUP_CONCAT($alias.structure_id)", 'structure_id');

      $results = $query->execute()->fetchAllAssoc('id', \PDO::FETCH_OBJ);
      $result = array_shift($results);

      if ($result) {
        unset($result->id);
      }

      return $result;

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting orientations: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  public function loadStructures(object $orientations): array {
    $structures = [];

    if ($orientations->point_entree_structure == 1) {
      $structures[$orientations->EPCI_id] = $this->loadEpci((int) $orientations->EPCI_id);
    }
    else {
      unset($orientations->ori_id);
      unset($orientations->EPCI_id);
      unset($orientations->point_entree_structure);

      foreach ($orientations as $orientation) {
        if (!empty($orientation) && $orientation !== "(NULL)") {
          $explodeStructure = explode(',', $orientation);
          foreach ($explodeStructure as $structureId) {
            $structures[$structureId] = $this->loadStructure((int) $structureId);
          }
        }
      }
    }

    return $structures;
  }

  protected function loadStructure(int $structureId): ?object {
    if (!$this->validator->validateStructureId($structureId)) {
      $this->logger->warning('Invalid structure ID: @id', ['@id' => $structureId]);
      return NULL;
    }

    try {
      $query = $this->database->select('structure_', 's');
      $query->leftJoin('structure_identification', 'si', 's.structure_identification_id=si.id');
      $query->leftJoin('structure_adresse', 'sa', 's.structure_adresse_id=sa.id');
      $query->leftJoin('structure_statut', 'ss', 's.structure_statut_id=ss.id');
      $query->condition('ss.enabled', 1, '=');
      $query->condition('si.id', $structureId, '=');
      $query->fields('si', ['id', 'nom']);
      $query->fields('sa', ['id', 'adresse1', 'adresse2', 'code_postal', 'ville', 'telephone', 'site_internet']);

      $r = $query->execute()->fetchAllAssoc('id', \PDO::FETCH_OBJ);
      return array_shift($r);

    }
    catch (\Exception $e) {
      $this->logger->error('Error loading structure: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  protected function loadEpci(int $epciId): ?object {
    if (!$this->validator->validateStructureId($epciId)) {
      $this->logger->warning('Invalid EPCI ID: @id', ['@id' => $epciId]);
      return NULL;
    }

    try {
      $query = $this->database->select('EPCI_', 'E');
      $query->condition('E.id', $epciId, '=');
      $query->addField('E', 'id', 'id');
      $query->addField('E', 'nom_affichage', 'nom');
      $query->addField('E', 'adresse_1', 'adresse1');
      $query->addField('E', 'adresse_2', 'adresse2');
      $query->addField('E', 'code_postal', 'code_postal');
      $query->addField('E', 'ville', 'ville');
      $query->addField('E', 'telephone', 'telephone');
      $query->addField('E', 'site_internet', 'site_internet');

      $r = $query->execute()->fetchAllAssoc('id', \PDO::FETCH_OBJ);
      return array_shift($r);

    }
    catch (\Exception $e) {
      $this->logger->error('Error loading EPCI: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  public function formatStructureResults(array $structures): array {
    if (empty($structures)) {
      return [];
    }

    $results = [];
    shuffle($structures);

    foreach ($structures as $structure) {
      if (isset($structure->id)) {
        $randomKey = mt_rand();
        $siteUrl = htmlspecialchars($structure->site_internet ?? '', ENT_QUOTES, 'UTF-8');
        $results[$randomKey] = [
          'nom' => htmlspecialchars($structure->nom ?? '', ENT_QUOTES, 'UTF-8'),
          'adresse1' => htmlspecialchars($structure->adresse1 ?? '', ENT_QUOTES, 'UTF-8'),
          'adresse2' => htmlspecialchars($structure->adresse2 ?? '', ENT_QUOTES, 'UTF-8'),
          'code_postal' => htmlspecialchars($structure->code_postal ?? '', ENT_QUOTES, 'UTF-8'),
          'ville' => htmlspecialchars($structure->ville ?? '', ENT_QUOTES, 'UTF-8'),
          'telephone' => htmlspecialchars($structure->telephone ?? '', ENT_QUOTES, 'UTF-8'),
          'site_internet' => '<a class="custom-link-highlighted" href="' . $siteUrl . '" target="_blank" rel="noopener noreferrer">' . $siteUrl . '</a>',
        ];
      }
    }

    return $results;
  }

  public function isSareValid(string $revenu, ?object $orientations): bool {
    if ($orientations === NULL) {
      return FALSE;
    }

    return !($revenu === 'sup' && $orientations->participation_SARE == 0);
  }

}
