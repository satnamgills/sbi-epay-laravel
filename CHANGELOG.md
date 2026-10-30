# Release Notes

## [Unreleased](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.2.0...HEAD)

## [v1.0.1](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.0...v1.0.1) - 2026-10-15

### Fixed

- Fixed PHPStan static analysis check for package view string inference in `PaymentInitiation`.
- Added resilient `reportUnmatchedIgnoredErrors: false` configuration to `phpstan.neon.dist`.
- Corrected GitHub Actions CI test matrix to run on active PHP and Laravel framework versions.

## [v1.2.0](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.1.1...v1.2.0) - 2026-10-30

### [1.2.0](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.1.1...v1.2.0) (2026-10-30)

#### Features

* enhance double verification process with additional parameters and examples in documentation ([921f09e](https://github.com/satnamgills/sbi-epay-laravel/commit/921f09ec82bf34e395580e9d3a2062bbef2d06e2))

## [v1.1.1](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.1.0...v1.1.1) - 2026-10-30

### [1.1.1](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.1.0...v1.1.1) (2026-10-30)

## [v1.1.0](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.10...v1.1.0) - 2026-10-30

### [1.1.0](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.10...v1.1.0) (2026-10-30)

#### Features

* enhance payment initiation with account routing and user details support ([f279cbb](https://github.com/satnamgills/sbi-epay-laravel/commit/f279cbb5607c872e1ca4d03dfe93015cb49e8b97))

## [v1.0.10](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.9...v1.0.10) - 2026-10-16

### [1.0.10](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.9...v1.0.10) (2026-10-16)

### Bug Fixes

* update payment and verification endpoints for test and live modes ([eb2a641](https://github.com/satnamgills/sbi-epay-laravel/commit/eb2a641c64816f10b57705aa8308641e3ddd6510))

### Documentation

* update example payment and verification URLs in README ([506e0a6](https://github.com/satnamgills/sbi-epay-laravel/commit/506e0a62618657a08fd6eef26a9cd099f3d3eeeb))

## [v1.0.9](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.8...v1.0.9) - 2026-10-15

### [1.0.9](https://github.com/satnamgills/sbi-epay-laravel/compare/v1.0.8...v1.0.9) (2026-10-15)

### Documentation

* fix license badge and standardize MIT license header ([7ec6eed](https://github.com/satnamgills/sbi-epay-laravel/commit/7ec6eed98d9face9123365293b795be9d9e93dbe))

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
