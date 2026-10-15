# Contributing to SBI ePay for Laravel

Thank you for your interest in contributing to **SBI ePay for Laravel**! We welcome bug fixes, documentation improvements, and feature proposals from the community.

Please review this guide before submitting a pull request or issue.

---

## Code of Conduct

All contributors are expected to adhere to our [Code of Conduct](CODE_OF_CONDUCT.md). Please report any unacceptable behavior to satnamgills@gmail.com.

---

## How Contributions Work (Fork & Pull Request)

To maintain stability and security for all package consumers:

1. **Direct pushes to `main` are restricted**: Nobody (including outside collaborators) can push or merge directly into the `main` branch.
2. **All changes go through Pull Requests**: Outside contributors fork the repository and open a Pull Request.
3. **Review & Merge**: Only repository maintainers can review, approve, and merge Pull Requests into `main`. Once merged into `main`, GitHub Actions CI validates the build and automatically cuts a new release version for Packagist.

---

## Development Workflow

### 1. Fork and Clone
1. Fork the repository on GitHub: [satnamgills/sbi-epay-laravel](https://github.com/satnamgills/sbi-epay-laravel).
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/sbi-epay-laravel.git
   cd sbi-epay-laravel
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

### 2. Create a Topic Branch
Create a descriptive branch for your work:
```bash
git checkout -b fix/callback-amount-parsing
# or
git checkout -b feat/add-refund-support
```

### 3. Follow Coding Standards
- **Strict Types**: Every PHP file must start with `declare(strict_types=1);`.
- **Laravel Idiomatic Code**: Adhere to standard Laravel conventions and service provider structure.
- **Formatting**: Run Laravel Pint to format code:
  ```bash
  composer lint
  ```

### 4. Write and Run Tests
Every new feature or bug fix must include corresponding tests using [Pest](https://pestphp.com/):
```bash
# Run the full test and validation suite (PHPStan, Pint, Type coverage, Pest)
composer test

# Run tests only
composer test:unit

# Run static analysis
composer analyse
```

### 5. Commit Guidelines (Conventional Commits)
This repository uses automated release tagging based on commit conventions:
- `fix: ...` $\rightarrow$ triggers a **Patch** release (e.g. `v1.0.1` $\rightarrow$ `v1.0.2`).
- `feat: ...` $\rightarrow$ triggers a **Minor** release (e.g. `v1.0.1` $\rightarrow$ `v1.1.0`).
- `docs: ...`, `chore: ...`, `refactor: ...` $\rightarrow$ updates the repository without bumping minor/major.
- `BREAKING CHANGE: ...` or `#major` $\rightarrow$ triggers a **Major** release (e.g. `v2.0.0`).

### 6. Submit a Pull Request
1. Push your branch to your fork:
   ```bash
   git push -u origin your-branch-name
   ```
2. Open a Pull Request against the `main` branch of `satnamgills/sbi-epay-laravel`.
3. Fill out the pull request template describing your changes and link any related issues.
4. Ensure the GitHub Actions CI checks pass green.

---

## Reporting Bugs & Security Vulnerabilities

- **Bug Reports**: Open an issue using the [Bug Report Template](https://github.com/satnamgills/sbi-epay-laravel/issues/new?template=bug.yml). Include steps to reproduce, Laravel version, and PHP version.
- **Security Vulnerabilities**: Please review our [Security Policy](SECURITY.md). **Do not** report security vulnerabilities in public issues; send an email directly to satnamgills@gmail.com.
