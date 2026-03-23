# Release Notes v1.0.0

Initial stable release of `rainwaves/paystack-payment`.

## Included

- Laravel service provider and published package config
- Paystack customer creation
- Paystack plan creation
- single-payment checkout initialization
- subscription checkout initialization
- transaction verification
- refund creation
- webhook signature verification

## Package Safety

- request validation before outbound Paystack calls
- explicit exceptions for invalid package input
- richer transaction verification DTO fields for host applications
- webhook helper methods for common event checks

## Developer Experience

- Laravel usage guide in the README
- currency handling based on the configured Paystack portfolio currency
- unit coverage across success paths and main failure branches

## Recommended Tag

- `v1.0.0`

## Packagist Note

Do not set package versions in `composer.json`. Packagist should read versions from Git tags.
