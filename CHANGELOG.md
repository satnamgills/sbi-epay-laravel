# Release Notes

## [Unreleased](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.8...HEAD)

## [v1.0.1](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.0...v1.0.1) - 2026-10-15

### Fixed

- Fixed PHPStan static analysis check for package view string inference in `PaymentInitiation`.
- Added resilient `reportUnmatchedIgnoredErrors: false` configuration to `phpstan.neon.dist`.
- Corrected GitHub Actions CI test matrix to run on active PHP and Laravel framework versions.

## [v1.0.8](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.0...v1.0.8) - 2026-10-15

### [1.0.8](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.7...v1.0.8) (2026-10-15)

### Continuous Integration

* enhance tag-and-release workflow with GitHub App token generation and update token usage ([0cda540](https://github.com/satnamgills/sbi-epay-laravel/commit/0cda54030a3b9c432e148d78492eea42a885a022))
* streamline tag and release workflow with GitHub App token and remove redundant changelog workflow ([d4a76b9](https://github.com/satnamgills/sbi-epay-laravel/commit/d4a76b98a8f7e3540907a0f3efe2f89efb18262b))

## [v1.0.0](https://github.com/satnamgills/sbi-epay-laravel/releases/tag/v1.0.0) - 2026-10-14

### Added

- Complete SBI ePay payment gateway integration for Laravel 12+.
- AES-128-CBC encryption and decryption support (`AESEncDec`).
- Data Transfer Objects: `PaymentRequest`, `PaymentInitiation`, `PaymentResponse`, `VerificationResponse`.
- Server-to-server Double Verification support via `SbiEpay::verify()`.
- Auto-submitting redirect view and helper methods.
- Full Pest test suite with 100% type coverage.
