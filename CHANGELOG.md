# Release Notes

## [Unreleased](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.0...main)

## [v1.0.1](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.0...v1.0.1) - 2026-10-15

### Fixed
- Fixed PHPStan static analysis check for package view string inference in `PaymentInitiation`.
- Added resilient `reportUnmatchedIgnoredErrors: false` configuration to `phpstan.neon.dist`.
- Corrected GitHub Actions CI test matrix to run on active PHP and Laravel framework versions.

## [v1.0.0](https://github.com/satnamgills/laravel-packages-sbi-epay/releases/tag/v1.0.0) - 2026-10-14

### Added
- Complete SBI ePay payment gateway integration for Laravel 12+.
- AES-128-CBC encryption and decryption support (`AESEncDec`).
- Data Transfer Objects: `PaymentRequest`, `PaymentInitiation`, `PaymentResponse`, `VerificationResponse`.
- Server-to-server Double Verification support via `SbiEpay::verify()`.
- Auto-submitting redirect view and helper methods.
- Full Pest test suite with 100% type coverage.

