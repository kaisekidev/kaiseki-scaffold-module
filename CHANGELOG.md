# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 - 2026-06-02

First tagged release, as part of the org-wide PHP 8.4 baseline migration
(Phase 5 — scaffolds). The generator now emits modules that are born compliant,
and the tool itself is on the baseline.

### Changed

- **BC: emitted modules target PHP `^8.2`** (was `>=8.2`) with caret-pinned
  dependencies throughout.
- Emitted `composer.json` (core + wordpress): PHPStan stack `^2.0`,
  `phpunit/phpunit ^11.0`, `maglnet/composer-require-checker ^4.0`,
  `phpstan/extension-installer ^1.4`, `bnf/phpstan-psr-container ^1.1`;
  dropped the direct `friendsofphp/php-cs-fixer` require (the shared standard
  owns it); `kaiseki/php-coding-standard ^1.0` (was `dev-master`). WordPress
  modules flip internal deps to `kaiseki/config ^2.0` + `kaiseki/wp-hook ^2.0`
  and `szepeviktor/phpstan-wordpress ^2.0`, dropping the now-redundant
  `php-stubs/wordpress-stubs`.
- Emitted `phpstan.neon` now includes the shared
  `vendor/kaiseki/php-coding-standard/phpstan/kaiseki.neon` and only declares
  its own paths.
- Emitted `phpunit.xml` migrated to the PHPUnit 11 schema (`cacheDirectory`,
  `<source>`, `failOnRisky`/`failOnWarning`; dropped `verbose`).
- Emitted CI is now a thin caller of the shared reusable workflow
  `kaisekidev/.github/.github/workflows/checks.yml@v1` (a real `checks.yml`,
  no longer the inert `checks.yaml.dist`), defaulting to `run-tests: false`.
- Emitted modules now ship `.github/dependabot.yml`,
  `.github/workflows/update-changelog.yml`, and a `require-checker.config.json`
  (WordPress modules whitelist the WP symbols they use).
- Emitted `.gitignore` uses `.phpunit.cache/` (was `.phpunit.result.cache`).
- The generator tool itself adopted the baseline: PHP `^8.2`, PHPStan 2 with
  the shared config (no `@phpstan-ignore`; iterators narrowed at the root),
  the shared reusable CI workflow, Dependabot, and changelog automation.

### Notes

- Supersedes `kaiseki/scaffold-wp-module`, which was a near-duplicate and has
  been archived; this scaffold already covers both core and WordPress modules.
