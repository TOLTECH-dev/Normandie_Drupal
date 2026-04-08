<?php

declare(strict_types=1);

namespace Drupal\trouver_conseiller\Service;

use Drupal\Core\Database\Connection;
use Drupal\normandie_core\Validator\NormandieValidator;
use Psr\Log\LoggerInterface;

final class CritereService {

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
    protected readonly NormandieValidator $validator,
  ) {}

  public function calculatePlafond(int $nbPersonnes): int {
    if (!$this->validator->validateNbPersonnes($nbPersonnes)) {
      $this->logger->warning('Invalid number of persons: @nb', ['@nb' => $nbPersonnes]);
      return 0;
    }

    $somme = 0;

    try {
      $query = $this->database->select('ANAH_critere', 'anah')
        ->fields('anah', ['id', 'nombre_personne', 'plafond_modeste', 'supplement_modeste'])
        ->execute();

      $critere = [];
      foreach ($query as $record) {
        $critere[(int) $record->nombre_personne] = (int) $record->plafond_modeste;
        if (!empty($record->supplement_modeste)) {
          $critere['supp'] = (int) $record->supplement_modeste;
        }
      }

      if ($nbPersonnes < 6) {
        $somme = $critere[$nbPersonnes] ?? 0;
      }
      else {
        $somme = $critere[5] ?? 0;
        for ($i = $nbPersonnes; $i > 5; $i--) {
          $somme += ($critere['supp'] ?? 0);
        }
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Error calculating plafond: @error', ['@error' => $e->getMessage()]);
    }

    return $somme;
  }

}
