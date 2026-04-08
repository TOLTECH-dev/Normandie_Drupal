<?php

declare(strict_types=1);

namespace Drupal\chiffrescles\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

final class ChiffresclesService {

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
  ) {}

  public function getNombreDossiers(): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreCheques(): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNotNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getTypeCheques(): array {
    try {
      $result = $this->database->select('demande_', 'd')
        ->distinct()
        ->fields('d', ['type'])
        ->condition('d.type', [1, 3], 'IN')
        ->orderBy('d.type', 'ASC')
        ->execute()
        ->fetchAll();

      $data = ['Nombre' => []];
      foreach ($result as $record) {
        $type_label = match ((int) $record->type) {
          1 => "Audit énergétique",
          3 => "Travaux",
          default => NULL,
        };
        if ($type_label !== NULL) {
          $data['Nombre'][] = [
            'valeur' => (string) $record->type,
            'type' => $type_label,
          ];
        }
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreAuditeurs(): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->condition('ps.enabled', 1);
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '0']);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreRenovateurs(): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->condition('ps.enabled', 1);
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '1']);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombrePermanences(): array {
    try {
      $query = $this->database->select('structure_permanence', 'sp');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('structure__structure_permanence', 'ssp', 'ssp.structure_permanence_id = sp.id');
      $query->innerJoin('structure_', 's', 's.id = ssp.structure__id');
      $query->innerJoin('structure_statut', 'ss', 'ss.id = s.structure_statut_id');
      $query->condition('ss.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getListeDepartement(): array {
    try {
      $result = $this->database->select('up_departement', 'd')
        ->fields('d', ['departement_code'])
        ->condition('d.departement_code', ['14', '27', '50', '61', '76'], 'IN')
        ->orderBy('d.departement_code', 'ASC')
        ->execute()
        ->fetchAll();

      $data = ['structures' => []];
      foreach ($result as $record) {
        $data['structures'][] = ['DEPARTEMENT' => $record->departement_code];
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getListeVille(): array {
    try {
      $query = $this->database->select('up_ville', 'v');
      $query->distinct();
      $query->fields('v', ['nom', 'code_postal', 'code_insee']);
      $or = $query->orConditionGroup();
      $or->condition('v.code_postal', '14%', 'LIKE');
      $or->condition('v.code_postal', '27%', 'LIKE');
      $or->condition('v.code_postal', '50%', 'LIKE');
      $or->condition('v.code_postal', '61%', 'LIKE');
      $or->condition('v.code_postal', '76%', 'LIKE');
      $query->condition($or);
      $query->groupBy('v.code_insee');
      $query->groupBy('v.nom');
      $query->groupBy('v.code_postal');
      $query->orderBy('v.nom', 'ASC');
      $query->orderBy('v.code_postal', 'ASC');
      $result = $query->execute()->fetchAll();

      $data = ['structures' => []];
      foreach ($result as $record) {
        $data['structures'][] = [
          'INSEE' => $record->code_insee,
          'VILLE' => "{$record->nom} ( {$record->code_postal} )",
        ];
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getListeEpci(): array {
    try {
      $result = $this->database->select('EPCI_', 'e')
        ->fields('e', ['id', 'nom'])
        ->where("SUBSTR(e.code_postal, 1, 2) IN ('14', '27', '50', '61', '76')")
        ->condition('e.enabled', 1)
        ->orderBy('e.nom', 'ASC')
        ->execute()
        ->fetchAll();

      $data = ['epci' => []];
      foreach ($result as $record) {
        $data['epci'][] = [
          'ID_EPCI' => (int) $record->id,
          'NOM_EPCI' => $record->nom,
        ];
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getListeVilleParDepartement(int $departement): array {
    try {
      $result = $this->database->select('up_ville', 'v')
        ->distinct()
        ->fields('v', ['nom', 'code_postal', 'code_insee'])
        ->where("SUBSTR(v.code_postal, 1, 2) = :departement", [':departement' => $departement])
        ->execute()
        ->fetchAll();

      $data = ['structures' => []];
      foreach ($result as $record) {
        $data['structures'][] = [
          'INSEE' => $record->code_insee,
          'VILLE' => "{$record->nom} ( {$record->code_postal} )",
        ];
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParCodeDepartement(int $codedep): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->where("SUBSTR(l.code_postal, 1, 2) = :codedep", [':codedep' => $codedep]);
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParCodeDepartement(int $codedep): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->where("SUBSTR(l.code_postal, 1, 2) = :codedep", [':codedep' => $codedep]);
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNotNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreAuditeursParCodeDepartement(int $codedep): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pa', 'p.partenaire_adresse_id = pa.id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(pa.code_postal, 1, 2) = :codedep", [':codedep' => (string) $codedep]);
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '0']);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreRenovateursParCodeDepartement(int $codedep): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pa', 'p.partenaire_adresse_id = pa.id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(pa.code_postal, 1, 2) = :codedep", [':codedep' => (string) $codedep]);
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '1']);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombrePermanencesParCodeDepartement(int $codedep): array {
    try {
      $query = $this->database->select('structure_permanence', 'sp');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('structure__structure_permanence', 'ssp', 'ssp.structure_permanence_id = sp.id');
      $query->innerJoin('structure_', 's', 's.id = ssp.structure__id');
      $query->innerJoin('structure_statut', 'ss', 'ss.id = s.structure_statut_id');
      $query->condition('ss.enabled', 1);
      $query->where("SUBSTR(sp.code_postal, 1, 2) = :codedep", [':codedep' => $codedep]);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParVille(int $insee): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->condition('l.INSEE', $insee);
      $query->condition('d.type', [2], 'NOT IN');
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParVille(int $insee): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->condition('l.INSEE', $insee);
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNotNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreAuditeursParVille(int $insee): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pad.code_postal AND upv.nom = pad.ville');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '0']);
      $query->condition('upv.code_insee', $insee);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreRenovateursParVille(int $insee): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pad.code_postal AND upv.nom = pad.ville');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '1']);
      $query->condition('upv.code_insee', $insee);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombrePermanencesParVille(int $codeinsee): array {
    try {
      $query = $this->database->select('structure_permanence', 'sp');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('structure__structure_permanence', 'ssp', 'ssp.structure_permanence_id = sp.id');
      $query->innerJoin('structure_', 's', 's.id = ssp.structure__id');
      $query->innerJoin('up_ville', 'v', 'v.code_postal = sp.code_postal AND v.nom = sp.ville');
      $query->innerJoin('structure_statut', 'ss', 'ss.id = s.structure_statut_id');
      $query->condition('ss.enabled', 1);
      $query->condition('v.code_insee', $codeinsee);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParEpci(int $epciid): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->innerJoin('up_ville', 'uv', 'uv.code_postal = l.code_postal AND uv.nom = l.ville');
      $query->innerJoin('orientation', 'o', 'o.ville_id = uv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = o.EPCI_id');
      $query->condition('e.id', $epciid);
      $query->condition('d.type', [2], 'NOT IN');
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParEpci(int $epciid): array {
    try {
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->innerJoin('up_ville', 'uv', 'uv.code_postal = l.code_postal AND uv.nom = l.ville');
      $query->innerJoin('orientation', 'o', 'o.ville_id = uv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = o.EPCI_id');
      $query->condition('e.id', $epciid);
      $query->condition('d.type', [2], 'NOT IN');
      $query->isNotNull('d.dateCP_id');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreAuditeursParEpci(int $epciid): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pad.code_postal AND upv.nom = pad.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = upv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '0']);
      $query->condition('e.id', $epciid);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreRenovateursParEpci(int $epciid): array {
    try {
      $query = $this->database->select('partenaire_', 'p');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pad.code_postal AND upv.nom = pad.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = upv.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->where("SUBSTR(p.type, 1, 1) = :type", [':type' => '1']);
      $query->condition('e.id', $epciid);
      $query->condition('ps.enabled', 1);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombrePermanencesParEpci(int $epciid): array {
    try {
      $query = $this->database->select('structure_permanence', 'sp');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('structure__structure_permanence', 'ssp', 'ssp.structure_permanence_id = sp.id');
      $query->innerJoin('structure_', 's', 's.id = ssp.structure__id');
      $query->innerJoin('up_ville', 'v', 'v.code_postal = sp.code_postal AND v.nom = sp.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = v.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->innerJoin('structure_statut', 'ss', 'ss.id = s.structure_statut_id');
      $query->condition('ss.enabled', 1);
      $query->condition('e.id', $epciid);
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParTypeCheque(int $typecheque): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParTypeCheque(int $typecheque): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->isNotNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParTypeChequeEtEpci(int $typecheque, int $epci): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->innerJoin('up_ville', 'v', 'v.code_postal = l.code_postal AND v.nom = l.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = v.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->condition('e.id', $epci);
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParTypeChequeEtEpci(int $typecheque, int $epci): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->innerJoin('up_ville', 'v', 'v.code_postal = l.code_postal AND v.nom = l.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = v.id');
      $query->innerJoin('EPCI_', 'e', 'e.id = ori.EPCI_id');
      $query->condition('e.id', $epci);
      $query->isNotNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParTypeChequeEtCodeDep(int $typecheque, int $codedep): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->where("SUBSTR(l.code_postal, 1, 2) = :codedep", [':codedep' => $codedep]);
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParTypeChequeEtCodeDep(int $typecheque, int $codedep): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->where("SUBSTR(l.code_postal, 1, 2) = :codedep", [':codedep' => $codedep]);
      $query->isNotNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreDossiersParTypeChequeEtVille(int $typecheque, int $codeinsee): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->condition('l.INSEE', $codeinsee);
      $query->condition('d.statut_id', [15], 'NOT IN');
      $query->isNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  public function getNombreChequesParTypeChequeEtVille(int $typecheque, int $codeinsee): array {
    try {
      $typeChequeArray = $this->transformTypeChequeInArray($typecheque);
      $query = $this->database->select('demande_', 'd');
      $query->addExpression('COUNT(*)', 'nombre');
      $query->innerJoin('logement', 'l', 'd.logement_id = l.id');
      $query->condition('l.INSEE', $codeinsee);
      $query->isNotNull('d.dateCP_id');
      $query->condition('d.type', $typeChequeArray, 'IN');
      $result = $query->execute()->fetchField();

      return [
        'Nombre' => [
          ['Nombre' => (int) $result],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Database error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return ['message' => 'no data found'];
    }
  }

  private function transformTypeChequeInArray(int $typeCheque): array {
    return $typeCheque === 1 ? [1, 4] : [$typeCheque];
  }

}
