# kaiseki/scaffold-module

Scaffold a Kaiseki WordPress or Core module.

This is a `composer create-project` template, not a library. Running it
interactively generates a new `kaiseki/*` package — WordPress or Core — already
on the org baseline (PHP `^8.2`, PHPStan 2, PHPUnit 11, the shared reusable CI
workflow, Dependabot, and changelog automation).

## Installation

```bash
composer create-project --no-dev kaiseki/scaffold-module <directory>
```

The `post-create-project` hook runs `bin/console bootstrap-module`, which asks
for the module type (wordpress | core), name, namespace, config base key, repo
URL, and copyright holder, then rewrites the checkout into the generated module
and runs `composer update`.

## Usage

Answer the prompts; the resulting package is ready to `git init`, push, and open
its first PR against. A scaffolded module ships the canonical CI caller verbatim,
which runs the full suite — phpunit + a 100% coverage gate across PHP
8.2/8.3/8.4. A fresh module has no tests yet, so its first CI run goes red until
you either add tests or uncomment the per-package override in the generated
`.github/workflows/checks.yml` (`run-tests: false` for static analysis only, or
`coverage-threshold: 0` to relax the gate while coverage catches up).

## Org-wide baselines

This repo is the canonical home for two static baseline files that every
`kaiseki/*` package (and `kaiseki-org`'s tooling, e.g. `apply-dependabot-config.sh`)
copies in. Edit them here:

- **`templates/phpunit.xml`** — the single PHPUnit 11 baseline for all modules.
  It carries a `%test_namespace%` placeholder that the scaffold fills in at
  generate time (WordPress modules nest tests under an extra `WordPress\`
  segment; core modules don't), so there's one file instead of a per-type copy.
- **`templates/shared/.github/dependabot.yml`** — the single Dependabot baseline.
  Dev deps and GitHub Actions are each grouped into one weekly PR; runtime deps
  get individual PRs. Production-major holds are a deliberate per-package choice
  (`ignore:`), not baked into the baseline.

The two CI caller workflows the scaffold emits (`.github/workflows/checks.yml`
and `update-changelog.yml`) are **not** owned here, and the scaffold keeps **no
copy** of them. `bin/console bootstrap-module` fetches them verbatim from the
canonical org starter workflows in
[kaisekidev/.github](https://github.com/kaisekidev/.github/tree/master/workflow-templates)
at generate time, so that public repo is the single source of truth and the
callers can never drift. (This means generating a module requires network access
to `.github`; there is no offline fallback. Edit the callers in `.github`.)

## Development

```bash
composer check            # check-deps + cs-check + phpstan
composer cs-fix           # apply php-cs-fixer fixes
```

`test-create-module.sh <branch> <dest>` generates a throwaway module from a
local branch so you can verify template changes end to end. Never run
`bin/console bootstrap-module` in this checkout directly — it rewrites the repo.

## License

Proprietary. Copyright © Kaiseki.
