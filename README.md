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
its first PR against. A scaffolded module ships with CI defaulting to static
analysis only (`run-tests: false`) — flip that on once you add real tests (see
the comment in the generated `.github/workflows/checks.yml`).

## Development

```bash
composer check       # check-deps + cs-check + phpstan
composer cs-fix      # apply php-cs-fixer fixes
```

`test-create-module.sh <branch> <dest>` generates a throwaway module from a
local branch so you can verify template changes end to end. Never run
`bin/console bootstrap-module` in this checkout directly — it rewrites the repo.

## License

Proprietary. Copyright © Kaiseki.
