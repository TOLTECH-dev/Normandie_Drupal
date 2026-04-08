<?php

declare(strict_types=1);

namespace Drupal\normandie_core\Validator;

/**
 * Central validator service for Normandie region data.
 *
 * Provides validation for common input types used across Normandie modules.
 */
final class NormandieValidator {

  /**
   * Normandie département codes.
   */
  private const NORMANDIE_DEPARTEMENTS = ['14', '27', '50', '61', '76'];

  /**
   * Maximum reasonable ID value.
   */
  private const MAX_ID = 999999;

  /**
   * Validates and sanitizes a structure identifier.
   *
   * @param string $identifiantStructure
   *   The structure identifier (e.g., 'S123' or 'E456').
   *
   * @return array
   *   Array with 'valid' (bool), 'letter' (string), and 'id' (int) keys.
   */
  public function validateStructureIdentifier(string $identifiantStructure): array {
    // Limit length to prevent memory exhaustion.
    if (strlen($identifiantStructure) > 20) {
      return ['valid' => FALSE, 'letter' => '', 'id' => 0];
    }

    // Validate format: Must start with S or E followed by digits.
    if (!preg_match('/^([SE])(\d+)$/', $identifiantStructure, $matches)) {
      return ['valid' => FALSE, 'letter' => '', 'id' => 0];
    }

    $letter = $matches[1];
    $id = (int) $matches[2];

    // Validate ID is positive and within reasonable range.
    if ($id <= 0 || $id > self::MAX_ID) {
      return ['valid' => FALSE, 'letter' => '', 'id' => 0];
    }

    return ['valid' => TRUE, 'letter' => $letter, 'id' => $id];
  }

  /**
   * Validates a département code.
   *
   * @param string $departement
   *   The département code (2 digits).
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateDepartement(string $departement): bool {
    return in_array($departement, self::NORMANDIE_DEPARTEMENTS, TRUE);
  }

  /**
   * Validates a postal code.
   *
   * @param string $codePostal
   *   The postal code (5 digits).
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateCodePostal(string $codePostal): bool {
    // Must be exactly 5 digits and start with Normandie département.
    if (!preg_match('/^(\d{2})\d{3}$/', $codePostal, $matches)) {
      return FALSE;
    }

    return in_array($matches[1], self::NORMANDIE_DEPARTEMENTS, TRUE);
  }

  /**
   * Validates a postal code prefix.
   *
   * @param string|null $postalCodePrefix
   *   The postal code prefix (2 to 5 digits).
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validatePostalCodePrefix(?string $postalCodePrefix): bool {
    if ($postalCodePrefix === NULL) {
      return FALSE;
    }

    // Must be 2 to 5 digits.
    if (!ctype_digit($postalCodePrefix)) {
      return FALSE;
    }

    $length = strlen($postalCodePrefix);
    if ($length < 2 || $length > 5) {
      return FALSE;
    }

    // Must start with Normandie département code.
    $prefix = substr($postalCodePrefix, 0, 2);
    return in_array($prefix, self::NORMANDIE_DEPARTEMENTS, TRUE);
  }

  /**
   * Validates an INSEE code.
   *
   * @param string|int $codeInsee
   *   The INSEE code (5 digits).
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateCodeInsee(string|int $codeInsee): bool {
    $code = (string) $codeInsee;

    // Must be exactly 5 digits.
    if (!preg_match('/^\d{5}$/', $code)) {
      return FALSE;
    }

    // If integer, verify range.
    if (is_int($codeInsee)) {
      return $codeInsee >= 10000 && $codeInsee <= 99999;
    }

    return TRUE;
  }

  /**
   * Validates an EPCI ID.
   *
   * @param int $epciId
   *   The EPCI ID.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateEpciId(int $epciId): bool {
    return $epciId > 0 && $epciId <= self::MAX_ID;
  }

  /**
   * Validates a structure ID.
   *
   * @param int $structureId
   *   The structure ID.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateStructureId(int $structureId): bool {
    return $structureId > 0 && $structureId <= self::MAX_ID;
  }

  /**
   * Validates a ville ID.
   *
   * @param int $villeId
   *   The ville ID.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateVilleId(int $villeId): bool {
    return $villeId > 0 && $villeId <= self::MAX_ID;
  }

  /**
   * Validates number of persons in a household.
   *
   * @param int $nbPersonnes
   *   The number of persons.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateNbPersonnes(int $nbPersonnes): bool {
    // Must be between 1 and 20 (reasonable household size).
    return $nbPersonnes >= 1 && $nbPersonnes <= 20;
  }

  /**
   * Validates a revenue state parameter.
   *
   * @param string $state
   *   The state ('inf' or 'sup').
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateRevenueState(string $state): bool {
    return in_array($state, ['inf', 'sup'], TRUE);
  }

  /**
   * Validates a type cheque parameter.
   *
   * @param int $typeCheque
   *   The type cheque value (1 or 3).
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateTypeCheque(int $typeCheque): bool {
    return in_array($typeCheque, [1, 3], TRUE);
  }

  /**
   * Gets the list of valid Normandie département codes.
   *
   * @return array
   *   Array of département codes.
   */
  public function getNormandieDepartements(): array {
    return self::NORMANDIE_DEPARTEMENTS;
  }

}
