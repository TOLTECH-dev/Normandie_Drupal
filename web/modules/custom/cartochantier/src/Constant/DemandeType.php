<?php

declare(strict_types=1);

namespace Drupal\cartochantier\Constant;

final class DemandeType {

  public const AUDIT = 1;
  public const TRAVAUX = 3;
  public const AUDIT_LEGACY = 4;

  public const VALID_TYPES = [
    self::AUDIT,
    self::TRAVAUX,
    self::AUDIT_LEGACY,
  ];

  private function __construct() {
    /* Prevent instantiation: this class only defines constants and should not be instantiated. */
  }

}
