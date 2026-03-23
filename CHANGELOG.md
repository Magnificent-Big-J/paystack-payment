# Changelog

All notable changes to `rainwaves/paystack-payment` will be documented in this file.

The format is based on Keep a Changelog and this package follows semantic versioning at the package level.

## [1.0.0] - 2026-03-09

### Added
- Initial `rainwaves/paystack-payment` package scaffold and Laravel service provider.
- Provider-agnostic payment gateway contract and DTO set for checkout and webhooks.
- Paystack driver with:
  - customer creation
  - plan creation
  - transaction initialization
  - transaction verification
  - webhook signature verification
  - refund creation

### Changed
- Established the billing rule used by the host app:
  - catalog pricing remains in `USD`
  - Paystack checkout/subscription charges are created in `ZAR`
  - checkout amounts are sent in Paystack subunits

### Notes
- This package currently ships with Paystack as the first supported driver.
- Recurring billing support is designed for host applications that create or reuse Paystack customers and plans, then initialize checkout against those provider-side artifacts.
