# Normandie Core Module

## Description
Core module providing shared services and utilities for all Normandie region modules.

## Purpose
This module centralizes common functionality used across multiple Normandie modules to:
- Follow the DRY (Don't Repeat Yourself) principle
- Ensure consistent validation logic
- Simplify maintenance and updates
- Provide a single source of truth for Normandie-specific rules

## Services Provided

### NormandieValidator
Central validation service for all Normandie region data inputs.

**Location:** `Drupal\normandie_core\Validator\NormandieValidator`

**Service ID:** `normandie_core.validator`

#### Available Validation Methods:

- `validateStructureIdentifier(string $id): array` - Validates structure IDs (S123, E456)
- `validateDepartement(string $code): bool` - Validates département codes (14, 27, 50, 61, 76)
- `validateCodePostal(string $code): bool` - Validates postal codes (5 digits, Normandie only)
- `validatePostalCodePrefix(?string $prefix): bool` - Validates postal code prefixes (2-5 digits)
- `validateCodeInsee(string|int $code): bool` - Validates INSEE codes (5 digits)
- `validateEpciId(int $id): bool` - Validates EPCI IDs (1-999999)
- `validateStructureId(int $id): bool` - Validates structure IDs (1-999999)
- `validateVilleId(int $id): bool` - Validates ville IDs (1-999999)
- `validateNbPersonnes(int $nb): bool` - Validates household size (1-20)
- `validateRevenueState(string $state): bool` - Validates revenue state ('inf', 'sup')
- `getNormandieDepartements(): array` - Returns list of Normandie département codes

## Security Features

### Input Validation
- **Length Limits:** Prevents memory exhaustion attacks
- **Format Validation:** Strict regex patterns for all inputs
- **Range Validation:** Ensures IDs are within reasonable bounds
- **Whitelist Validation:** Only allows specific values for départements and states
- **Region Restriction:** All validations specific to Normandie region

### Constants
- `NORMANDIE_DEPARTEMENTS`: ['14', '27', '50', '61', '76']
- `MAX_ID`: 999999

## Usage Example

### In your service:

```php
use Drupal\normandie_core\Validator\NormandieValidator;

final class MyService {
  
  public function __construct(
    protected readonly NormandieValidator $validator,
  ) {}
  
  public function processPostalCode(string $code): bool {
    if (!$this->validator->validateCodePostal($code)) {
      return false;
    }
    // Process valid postal code...
  }
}
```

### In your services.yml:

```yaml
services:
  my_module.my_service:
    class: Drupal\my_module\Service\MyService
    arguments:
      - '@normandie_core.validator'
```

## Dependencies
- Drupal Core ^10

## Dependent Modules
- `cartostructure` - Uses validator for structure and location validation
- `trouver_conseiller` - Uses validator for search criteria validation

## Architecture
Follows SOLID principles:
- **Single Responsibility:** Each validation method has one clear purpose
- **Open/Closed:** Easily extended for new validation rules
- **Dependency Inversion:** Modules depend on validator abstraction

## Maintainer
Normandie Development Team
