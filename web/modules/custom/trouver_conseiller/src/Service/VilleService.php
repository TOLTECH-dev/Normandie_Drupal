<?php

declare(strict_types=1);

namespace Drupal\trouver_conseiller\Service;

use Drupal\Core\Database\Connection;
use Drupal\normandie_core\Validator\NormandieValidator;
use Psr\Log\LoggerInterface;

final class VilleService {

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly Connection $database,
    protected readonly NormandieValidator $validator,
  ) {}

  public function getVillesInit(): array {
    $villes = [];

    try {
      $or = $this->database->condition('OR');
      $or->condition('code_postal', '14%', 'LIKE');
      $or->condition('code_postal', '27%', 'LIKE');
      $or->condition('code_postal', '50%', 'LIKE');
      $or->condition('code_postal', '61%', 'LIKE');
      $or->condition('code_postal', '76%', 'LIKE');

      $query = $this->database->select('up_ville', 'v')
        ->fields('v', ['code_insee', 'nom'])
        ->condition($or)
        ->orderBy('nom', 'ASC')
        ->orderBy('code_postal', 'ASC');

      $results = $query->execute();

      foreach ($results as $record) {
        $villes[$record->code_insee] = $record->nom;
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Error loading villes: @error', ['@error' => $e->getMessage()]);
    }

    return $villes;
  }

  public function getVillesByPostalCode(?string $postalCodePrefix): array {
    if (!$this->validator->validatePostalCodePrefix($postalCodePrefix)) {
      $this->logger->warning('Invalid postal code prefix: @prefix', ['@prefix' => $postalCodePrefix ?? 'NULL']);
      return [];
    }

    $villes = [];

    try {
      $query = $this->database->select('up_ville', 'v')
        ->fields('v', ['nom', 'code_postal', 'code_insee'])
        ->condition('code_postal', $postalCodePrefix . '%', 'LIKE')
        ->orderBy('nom', 'ASC')
        ->orderBy('code_postal', 'ASC');

      $results = $query->execute();

      foreach ($results as $record) {
        $villes[] = [
          'CP' => $record->code_insee,
          'VILLE' => $record->nom,
        ];
      }

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting villes by postal code: @error', ['@error' => $e->getMessage()]);
    }

    return $villes;
  }

  public function getVilleIdByCodeInsee(int $codeInsee): ?int {
    if (!$this->validator->validateCodeInsee($codeInsee)) {
      $this->logger->warning('Invalid INSEE code: @code', ['@code' => $codeInsee]);
      return NULL;
    }

    try {
      $id = $this->database->select('up_ville', 'v')
        ->fields('v', ['id'])
        ->condition('code_insee', $codeInsee, '=')
        ->execute()
        ->fetchField();

      return $id ? (int) $id : NULL;

    }
    catch (\Exception $e) {
      $this->logger->error('Error getting ville ID: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

}
