<?php

declare(strict_types=1);

namespace Drupal\cartochantier\Service;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\cartochantier\Constant\DemandeType;
use Psr\Log\LoggerInterface;
use Drupal\Core\Database\Connection;

final class CartochantierService {

  private const NUMERO_OPERATION = 588;

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
    protected readonly ExtensionPathResolver $extensionPathResolver,
  ) {}

  public function getDemande(): array {
    $sql = $this->getBaseDemandeSql();

    try {
      $result = $this->database->query($sql, [':numero_operation' => self::NUMERO_OPERATION])->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeDepartement(): array {
    try {
      $result = $this->database->select('up_departement')
        ->fields(NULL, ['departement_code'])
        ->condition('departement_code', ['14', '27', '50', '61', '76'], 'IN')
        ->orderBy('departement_code', 'ASC')
        ->execute()
        ->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = ['DEPARTEMENT' => $row->departement_code];
      }

      return ['structures' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getTypeDemande(): array {
    try {
      $result = $this->database->select('demande_')
        ->distinct()
        ->fields(NULL, ['type'])
        ->orderBy('type', 'ASC')
        ->execute()
        ->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $type = (int) $row->type;
        $label = match($type) {
          DemandeType::AUDIT => 'Audit énergétique',
          DemandeType::TRAVAUX => 'Travaux',
          default => NULL,
        };

        if ($label) {
          $data[] = ['valeur' => $type, 'type' => $label];
        }
      }

      return ['Nombre' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeCodePostal(): array {
    try {
      $query = $this->database->select('up_ville')
        ->distinct()
        ->fields(NULL, ['code_postal'])
        ->orderBy('code_postal', 'ASC');

      $orGroup = $query->orConditionGroup()
        ->condition('code_postal', '14%', 'LIKE')
        ->condition('code_postal', '27%', 'LIKE')
        ->condition('code_postal', '50%', 'LIKE')
        ->condition('code_postal', '61%', 'LIKE')
        ->condition('code_postal', '76%', 'LIKE');

      $query->condition($orGroup);
      $result = $query->execute()->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = ['CODE_POSTAL' => $row->code_postal];
      }

      return ['structures' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeVille(): array {
    try {
      $query = $this->database->select('up_ville')
        ->distinct()
        ->fields(NULL, ['nom', 'code_postal', 'code_insee'])
        ->groupBy('nom')
        ->groupBy('code_postal')
        ->groupBy('code_insee')
        ->orderBy('nom', 'ASC')
        ->orderBy('code_postal', 'ASC');

      $orGroup = $query->orConditionGroup()
        ->condition('code_postal', '14%', 'LIKE')
        ->condition('code_postal', '27%', 'LIKE')
        ->condition('code_postal', '50%', 'LIKE')
        ->condition('code_postal', '61%', 'LIKE')
        ->condition('code_postal', '76%', 'LIKE');

      $query->condition($orGroup);
      $result = $query->execute()->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'INSEE' => $row->code_insee,
          'VILLE' => "{$row->nom} ({$row->code_postal})",
        ];
      }

      return ['structures' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeEpci(): array {
    try {
      $result = $this->database->select('EPCI_')
        ->fields(NULL, ['id', 'nom'])
        ->where("SUBSTR(code_postal, 1, 2) IN ('14', '27', '50', '61', '76')")
        ->condition('enabled', 1)
        ->orderBy('nom', 'ASC')
        ->execute()
        ->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'ID_EPCI' => $row->id,
          'NOM_EPCI' => $row->nom,
        ];
      }

      return ['epci' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeVilleParCodePostal(string $codePostal): array {
    try {
      $result = $this->database->select('up_ville')
        ->distinct()
        ->fields(NULL, ['nom', 'code_postal', 'code_insee'])
        ->condition('code_postal', $codePostal)
        ->execute()
        ->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'INSEE' => $row->code_insee,
          'VILLE' => "{$row->nom} ({$row->code_postal})",
        ];
      }

      return ['structures' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getListeVilleParDepartement(string $departement): array {
    try {
      $result = $this->database->select('up_ville')
        ->distinct()
        ->fields(NULL, ['nom', 'code_postal', 'code_insee'])
        ->condition('code_postal', $departement . '%', 'LIKE')
        ->orderBy('nom', 'ASC')
        ->orderBy('code_postal', 'ASC')
        ->execute()
        ->fetchAll();
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'INSEE' => $row->code_insee,
          'VILLE' => "{$row->nom} ({$row->code_postal})",
        ];
      }

      return ['structures' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDemandParType(int $type): array {
    $typeArray = $this->transformTypeToArray($type);

    // Build named parameters for type array.
    $typeParams = [];
    $placeholders = [];
    foreach ($typeArray as $index => $typeValue) {
      $paramName = ':type_' . $index;
      $typeParams[$paramName] = $typeValue;
      $placeholders[] = $paramName;
    }

    $whereClause = " AND d.type IN (" . implode(',', $placeholders) . ")";
    $sql = $this->getBaseDemandeSql($whereClause);

    try {
      $params = array_merge($typeParams, [':numero_operation' => self::NUMERO_OPERATION]);
      $result = $this->database->query($sql, $params);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDemandParCodePostal(string $codePostal): array {
    $whereClause = " AND l.code_postal = :code_postal";
    $sql = $this->getBaseDemandeSql($whereClause);

    try {
      $result = $this->database->query($sql, [
        ':code_postal' => $codePostal,
        ':numero_operation' => self::NUMERO_OPERATION,
      ]);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDemandParDepartement(string $departement): array {
    $whereClause = " AND SUBSTR(l.code_postal, 1, 2) = :departement";
    $sql = $this->getBaseDemandeSql($whereClause);

    try {
      $result = $this->database->query($sql, [
        ':departement' => $departement,
        ':numero_operation' => self::NUMERO_OPERATION,
      ]);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDemandParVille(string $insee): array {
    $whereClause = " AND l.INSEE = :insee";
    $sql = $this->getBaseDemandeSql($whereClause);

    try {
      $result = $this->database->query($sql, [
        ':insee' => $insee,
        ':numero_operation' => self::NUMERO_OPERATION,
      ]);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDemandParEpci(int $epciId): array {
    $joinEpci = " INNER JOIN up_ville ON up_ville.code_postal = l.code_postal AND up_ville.nom = l.ville INNER JOIN orientation ON orientation.ville_id = up_ville.id INNER JOIN EPCI_ ON EPCI_.id = orientation.EPCI_id";
    $whereClause = " AND EPCI_.id = :epci_id";
    $sql = $this->getBaseDemandeSql($whereClause, $joinEpci);

    try {
      $result = $this->database->query($sql, [
        ':epci_id' => $epciId,
        ':numero_operation' => self::NUMERO_OPERATION,
      ]);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getTypeDemandParDepartement(int $type, string $departement): array {
    return $this->getFilteredDemandes($type, 'departement', $departement);
  }

  public function getTypeDemandParCodePostal(int $type, string $codePostal): array {
    return $this->getFilteredDemandes($type, 'code_postal', $codePostal);
  }

  public function getTypeDemandParVille(int $type, string $insee): array {
    return $this->getFilteredDemandes($type, 'insee', $insee);
  }

  public function getTypeDemandParEpci(int $type, int $epciId): array {
    return $this->getFilteredDemandes($type, 'epci', (string) $epciId);
  }

  private function getFilteredDemandes(int $type, string $filterType, string $filterValue): array {
    $typeArray = $this->transformTypeToArray($type);

    // Build named parameters for type array.
    $typeParams = [];
    $placeholders = [];
    foreach ($typeArray as $index => $typeValue) {
      $paramName = ':type_' . $index;
      $typeParams[$paramName] = $typeValue;
      $placeholders[] = $paramName;
    }

    $whereClause = match($filterType) {
      'departement' => " AND SUBSTR(l.code_postal, 1, 2) = :filter_value",
      'code_postal' => " AND l.code_postal = :filter_value",
      'insee' => " AND l.INSEE = :filter_value",
      'epci' => " AND EPCI_.id = :filter_value",
      default => "",
    };

    $joinEpci = $filterType === 'epci' ? " INNER JOIN up_ville ON up_ville.code_postal = l.code_postal AND up_ville.nom = l.ville INNER JOIN orientation ON orientation.ville_id = up_ville.id INNER JOIN EPCI_ ON EPCI_.id = orientation.EPCI_id" : "";

    // Get base SQL and append type filter.
    $baseWhereClause = $whereClause . " AND d.type IN (" . implode(',', $placeholders) . ")";
    $sql = $this->getBaseDemandeSql($baseWhereClause, $joinEpci);

    try {
      $params = array_merge(
        $typeParams,
        [':filter_value' => $filterValue, ':numero_operation' => self::NUMERO_OPERATION]
      );

      $result = $this->database->query($sql, $params);
      $data = [];

      foreach ($result as $row) {
        $data[] = [
          'IDDEMANDE' => $row->id,
          'CHEQUE' => $row->demandeType,
          'PROFESSIONNEL' => $row->professionnel,
          'CONSEILLER' => $row->conseiller,
          'STRUCTURE' => $row->structure,
          'LAT' => $row->latitude,
          'LONG' => $row->longitude,
          'STATUT' => $row->statut,
          'STATUT_LABEL' => $row->statutLabel,
          'CODE_POSTAL' => $row->code_postal,
          'VILLE' => $row->ville,
        ];
      }

      return ['Demande' => $data];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function getDepartementsMap(): ?string {
    try {
      $module_path = $this->extensionPathResolver->getPath('module', 'cartochantier');
      $file = $module_path . '/json/departements-normandie.json';

      if (file_exists($file)) {
        return file_get_contents($file);
      }

      $this->logger->warning('Departments map file not found: @file', ['@file' => $file]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error reading departments map: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  private function transformTypeToArray(int $type): array {
    return $type === DemandeType::AUDIT ? [DemandeType::AUDIT, DemandeType::AUDIT_LEGACY] : [$type];
  }

  private function getBaseDemandeSql(string $whereClause = '', string $joinEpci = ''): string {
    return "SELECT DISTINCT d.id, upc.latitude, upc.longitude,
              CASE d.type 
                WHEN 1 THEN 'Audit énergétique' 
                WHEN 3 THEN CASE SUBSTR(dtd.niveau, 1, 1)
                  WHEN 0 THEN 'Chèque travaux niveau I' 
                  WHEN 1 THEN 'Chèque travaux niveau II' 
                  WHEN 2 THEN 'Chèque travaux niveau II option rénovateur'
                  WHEN 3 THEN 'Chèque travaux BBC'
                  WHEN 4 THEN 'Chèque travaux BBC Biosourcé'
                  WHEN 6 THEN 'Chèque travaux Sortie de passoire'
                  WHEN 7 THEN 'Chèque travaux Première étape BBC avec RGE'
                  WHEN 8 THEN 'Chèque travaux Première étape BBC avec Rénovateur'
                  WHEN 9 THEN 'Chèque travaux Rénovation globale BBC'
                END
                WHEN 4 THEN 'Audit énergétique'
              END AS demandeType,
              CASE d.type 
                WHEN 1 THEN IF(sc_dae.nom IS NULL, '', CONCAT(sc_dae.nom, ' ', sc_dae.prenom))
                WHEN 3 THEN IF(sc_dt.nom IS NULL, '', CONCAT(sc_dt.nom, ' ', sc_dt.prenom))
                WHEN 4 THEN IF(sc_dae.nom IS NULL, '', CONCAT(sc_dae.nom, ' ', sc_dae.prenom))
              END AS conseiller,
              CASE d.type 
                WHEN 1 THEN IF(si_dae.nom IS NULL, '', si_dae.nom)
                WHEN 3 THEN IF(si_dt.nom IS NULL, '', si_dt.nom)
                WHEN 4 THEN IF(si_dae.nom IS NULL, '', si_dae.nom)
              END AS structure,
              CASE d.type 
                WHEN 1 THEN IF(pi_dae.raison_sociale IS NULL, ' ', pi_dae.raison_sociale)
                WHEN 3 THEN IF(pi_dtd.raison_sociale IS NULL, ' ', pi_dtd.raison_sociale)
                WHEN 4 THEN IF(pi_dae.raison_sociale IS NULL, ' ', pi_dae.raison_sociale)
              END AS professionnel,
              r.statut_id AS statut,
              IF(rs.statut IS NULL, 'En cours', IF(rs.statut = 22, 'Travaux terminés', 'En cours')) AS statutLabel,
              l.code_postal, l.ville
            FROM demande_ d
            INNER JOIN logement l ON l.id = d.logement_id
            INNER JOIN admin_coordonnee upc ON upc.object_id = l.id
            INNER JOIN beneficiaire b ON b.id = d.beneficiaire_id
            INNER JOIN demande_statut ds ON ds.id = d.statut_id
            $joinEpci
            LEFT JOIN demande_audit_energie dae ON d.demande_audit_energie_id = dae.id
            LEFT JOIN structure_ s_dae ON dae.structure_id = s_dae.id
            LEFT JOIN structure_identification si_dae ON s_dae.structure_identification_id = si_dae.id
            LEFT JOIN structure_conseiller sc_dae ON dae.conseiller_id = sc_dae.id
            LEFT JOIN partenaire_ p_dae ON dae.auditeur_id = p_dae.id
            LEFT JOIN partenaire_identification pi_dae ON p_dae.partenaire_identification_id = pi_dae.id
            LEFT JOIN demande_travaux dt ON d.demande_travaux_id = dt.id
            LEFT JOIN structure_ s_dt ON dt.structure_id = s_dt.id
            LEFT JOIN structure_identification si_dt ON s_dt.structure_identification_id = si_dt.id
            LEFT JOIN structure_conseiller sc_dt ON dt.conseiller_id = sc_dt.id
            LEFT JOIN demande_travaux_devis dtd ON dt.travaux_devis_id = dtd.id
            LEFT JOIN partenaire_ p_dtd ON dtd.renovateur_id = p_dtd.id
            LEFT JOIN partenaire_identification pi_dtd ON p_dtd.partenaire_identification_id = pi_dtd.id
            LEFT JOIN remboursement_ r ON r.demande_id = d.id
            LEFT JOIN remboursement_statut rs ON rs.id = r.statut_id
            LEFT JOIN titre t ON t.demande_id = d.id AND t.numero_operation != :numero_operation
            WHERE SUBSTR(upc.type, 1, 1) = 2 
              AND ds.statut IN (12, 13, 14) 
              AND d.type NOT IN (2, 5)
              $whereClause";
  }

}
