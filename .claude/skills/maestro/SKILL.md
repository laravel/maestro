# Maestro Skill

## What is Maestro

Maestro is the monorepo orchestrator for the official [Laravel starter kits](https://laravel.com/starter-kits). All starter kit source files live here and get built out to individual repositories. Changes are made in this repo, and Maestro automatically creates PRs for the affected starter kit repos after merge.

## Project Structure

```
maestro/
├── kits/              # Source files for all starter kits (inheritance-based layering)
├── orchestrator/      # Laravel app that builds and watches starter kits
├── build/             # Generated starter kit (git-ignored) — make changes here during dev
├── browser_tests/     # Cross-kit browser tests for CI
└── .github/           # GitHub workflows
```

## Commands

All commands below run from the `orchestrator/` directory unless noted otherwise.

| Command                                              | Description                                                                |
|------------------------------------------------------|----------------------------------------------------------------------------|
| `php artisan build`                                  | Build a starter kit into `build/`. Interactive or use flags (see below).   |
| `php artisan build --kit=vue`                        | Build Vue Fortify kit directly.                                            |
| `php artisan build --kit=svelte --blank`             | Build Blank Svelte kit.                                                    |
| `php artisan build --kit=livewire --workos`          | Build Livewire WorkOS kit.                                                 |
| `composer kit:run`                                   | Start dev server + file watcher (syncs `build/` back to `kits/`).          |
| `composer kits:pint`                                 | Run Pint on `kits/` and `browser_tests/`.                                  |
| `composer kits:lint`                                 | Run `kits:pint`, then lint/format Inertia variants and sync back.          |
| `composer kits:check`                                | Build and run CI checks (`composer setup && composer ci:check`) for all 13 kit variants sequentially. |
| `composer kits:browser-tests`                        | Build and run browser tests for all 4 Fortify kit variants (Livewire, React, Svelte, Vue). |
| `npm run watch:kits`                                 | Run only the file watcher (no dev server).                                 |
| `composer setup && composer ci:check`                | Run inside `build/` — installs deps, builds frontend, runs checks (see below). |

### Selective Execution

Pass `--livewire`, `--react`, `--svelte`, and/or `--vue` to target specific frameworks.
Pass `--blank`, `--fortify`, `--workos`, and/or `--components` to target specific variants.
Combine both to narrow down exactly which kit variants to run:

```bash
composer kits:check -- --react --svelte
composer kits:check -- --vue --svelte --fortify     # Vue and Svelte, Fortify variants only
composer kits:check -- --livewire --fortify --workos # Livewire Fortify and WorkOS only
composer kits:check -- --workos                      # all frameworks, WorkOS variant only
composer kits:lint -- --vue
composer kits:lint -- --livewire                     # runs only the shared Pint step (no frontend lint phase)
composer kits:browser-tests -- --vue
```

No flags runs the full default matrix for each command.

Available `--kit` values defined in `orchestrator/app/Enums/StarterKit.php`.

### CI Checks (`composer ci:check`)

Every starter kit has a `ci:check` composer script that validates the kit without auto-fixing. Run `composer setup` first to install dependencies and build the frontend (`composer setup` runs `composer install`, `npm install`, and `npm run build`, which generates the Wayfinder types needed by eslint and the type checker).

**Inertia kits** run: `eslint .`, `prettier --check .`, `tsc --noEmit` / `vue-tsc --noEmit` / `svelte-check`, then `@test` (pint + PHPUnit).

**Livewire kits** run: `@test` (pint + PHPUnit) only.

## Starter Kit Variants (13 total)

These are the full starter kit identifiers written to `orchestrator/storage/app/private/starter_kit` and used by `orchestrator/scripts/watch.js` for layer syncing.

| Stack     | Variant Identifier       | Auth / Feature Set        |
|-----------|--------------------------|---------------------------|
| Livewire  | `livewire-blank`         | Blank (no auth)           |
| Livewire  | `livewire`               | Fortify                   |
| Livewire  | `livewire-components`    | Fortify + Components      |
| Livewire  | `livewire-workos`        | WorkOS                    |
| React     | `react-blank`            | Blank (no auth)           |
| React     | `react`                  | Fortify                   |
| React     | `react-workos`           | WorkOS                    |
| Svelte    | `svelte-blank`           | Blank (no auth)           |
| Svelte    | `svelte`                 | Fortify                   |
| Svelte    | `svelte-workos`          | WorkOS                    |
| Vue       | `vue-blank`              | Blank (no auth)           |
| Vue       | `vue`                    | Fortify                   |
| Vue       | `vue-workos`             | WorkOS                    |

## Kit Inheritance Hierarchy

Starter kits are built by layering folders in priority order. Higher-priority layers override files from lower ones.

### Inertia (React / Svelte / Vue)

```
Shared/Blank
  → Inertia/Blank/Base
    → Inertia/Blank/{React|Svelte|Vue}
      → Shared/Base
        → Inertia/Base
          → Inertia/{React|Svelte|Vue}

For Fortify variant, add:
            → Shared/Fortify
              → Inertia/Fortify/Base
                → Inertia/Fortify/{React|Svelte|Vue}

For WorkOS variant, add:
            → Shared/WorkOS
              → Inertia/WorkOS/Base
                → Inertia/WorkOS/{React|Svelte|Vue}
```

### Livewire

```
Shared/Blank
  → Livewire/Blank
    → Shared/Base
      → Livewire/Base
        → Shared/Fortify → Livewire/Fortify [→ Livewire/Components]
        OR Shared/WorkOS → Livewire/WorkOS
```

### What This Means for Editing

- The watcher (`orchestrator/scripts/watch.js`) syncs changes from `build/` back to the **most specific layer** in `kits/`.
- If a file exists in both `Shared/Blank` and `Inertia/Fortify/Svelte`, editing it in `build/` syncs to `Inertia/Fortify/Svelte`.
- Files in `kits/Shared/` affect **all** kits. Files in `kits/Inertia/Svelte/` only affect Svelte.
- The watcher **restores placeholders** (e.g., `{{dashboard}}`) before syncing back — you don't need to worry about placeholders when editing in `build/`.

## Kits Folder Structure

```
kits/
├── Shared/
│   ├── Blank/       # Foundation: config, migrations, artisan, phpunit.xml, .env.example
│   ├── Base/        # Factories, bootstrap, gitignore
│   ├── Fortify/     # Auth Actions, Concerns, Providers, config/fortify.php
│   ├── WorkOS/      # WorkOS routes, migrations, config
│
├── Inertia/
│   ├── Blank/
│   │   ├── Base/          # Shared Inertia backend (controllers, middleware, routes)
│   │   ├── React/         # React blank resources
│   │   ├── Svelte/        # Svelte blank resources
│   │   └── Vue/           # Vue blank resources
│   ├── Base/              # Authenticated backend (settings controllers, middleware, tests)
│   ├── React/             # React authenticated frontend
│   ├── Svelte/            # Svelte authenticated frontend
│   ├── Vue/               # Vue authenticated frontend
│   ├── Fortify/
│   │   ├── Base/          # Fortify backend (auth controllers, providers, tests)
│   │   ├── React/         # React auth pages
│   │   ├── Svelte/        # Svelte auth pages
│   │   └── Vue/           # Vue auth pages
│   ├── WorkOS/
│   │   ├── Base/          # WorkOS backend
│   │   ├── React/         # React WorkOS pages
│   │   ├── Svelte/        # Svelte WorkOS pages
│   │   └── Vue/           # Vue WorkOS pages
│
└── Livewire/
    ├── Blank/
    ├── Base/
    ├── Fortify/
    ├── Components/        # Multi-file Blade components variant
    ├── WorkOS/
```

## Placeholder System

The build process replaces `{{placeholder}}` tokens in files with framework-specific values. The mapping lives in `orchestrator/scripts/ui-components.json`. For example:

- `{{dashboard}}` → `Dashboard` (Svelte/Vue) or `dashboard` (React)
- `{{auth_login}}` → `auth/Login` (Svelte/Vue) or `auth/login` (React)

Svelte and Vue use PascalCase page names. React uses kebab-case.

## Workflow

1. **Build**: `cd orchestrator && php artisan build --kit=svelte`
2. **Develop**: `composer kit:run` (starts dev server at localhost:8000 + watcher)
3. **Edit**: Make changes in `build/` — the watcher syncs them to `kits/`
4. **Test**: Inside `build/`, run `composer setup && composer ci:check`
5. **Commit**: Commit the changes in `kits/` (not `build/`)
6. **PR**: Create PR; after merge, Maestro auto-creates PRs for affected kit repos

## Browser Tests (Local CI Parity)

To run browser tests for all kits locally, run from the `orchestrator/` directory:

```bash
composer kits:browser-tests
```

This builds each Fortify variant (Livewire, React, Svelte, Vue), copies the shared browser tests, installs dependencies, and runs the tests — matching the steps in `.github/workflows/browser-tests.yml`.

To run the steps manually for a single kit, use the sequence below.

### Per-Kit Command Sequence

Run from the repo root unless noted:

```bash
# 1) Install orchestrator dependencies
cd orchestrator
composer install --no-interaction --prefer-dist

# 2) Build the target kit (replace <Kit> with Livewire, React, Svelte, or Vue)
php artisan build --kit=<Kit>
cd ..

# 3) Copy shared browser tests into build/
cp -r browser_tests/* build/

# 4) Install browser testing dependencies in build/
cd build
composer remove --dev phpunit/phpunit --no-interaction --no-update
composer require --dev pestphp/pest pestphp/pest-plugin-browser pestphp/pest-plugin-laravel --no-interaction
npm install
npm install playwright

# 5) Install Playwright browsers/deps
# CI installs browsers when cache miss, otherwise only system deps.
# For local parity, run browser install directly:
npx playwright install --with-deps

# 6) Prepare app env and build frontend assets
cp .env.example .env
php artisan key:generate
npm run build

# 7) Run browser tests
php vendor/bin/pest --parallel
cd ..
```

### Run All Matrix Kits Locally

```bash
for KIT in Livewire React Svelte Vue; do
  echo "Running browser tests for ${KIT}"

  cd orchestrator
  composer install --no-interaction --prefer-dist
  php artisan build --kit="${KIT}"
  cd ..

  cp -r browser_tests/* build/

  cd build
  composer remove --dev phpunit/phpunit --no-interaction --no-update
  composer require --dev pestphp/pest pestphp/pest-plugin-browser pestphp/pest-plugin-laravel --no-interaction
  npm install
  npm install playwright
  npx playwright install --with-deps
  cp .env.example .env
  php artisan key:generate
  npm run build
  php vendor/bin/pest --parallel
  cd ..
done
```

Kit names are case-sensitive here because the workflow matrix uses `Livewire`, `React`, `Svelte`, and `Vue` values directly for `php artisan build --kit=...`.

## Key Files Reference

| File                                                 | Purpose                                                         |
|------------------------------------------------------|-----------------------------------------------------------------|
| `orchestrator/app/Console/Commands/BuildCommand.php` | Build orchestration logic                                       |
| `orchestrator/app/Enums/StarterKit.php`              | Available kit enum                                              |
| `orchestrator/scripts/watch.js`                      | File watcher: syncs build → kits with placeholder restoration   |
| `orchestrator/scripts/run.js`                        | Dev server launcher                                             |
| `orchestrator/scripts/ui-components.json`            | Placeholder → framework-specific name mapping                   |
| `orchestrator/storage/app/private/starter_kit`       | Stores which kit is currently built                             |
| `orchestrator/CLAUDE.md`                             | Laravel Boost guidelines (PHP, Laravel 12, Pest 4, Tailwind v4) |

## Important Rules

1. **Edit in `build/`, commit in `kits/`**: Never edit `kits/` directly during development. The watcher handles syncing.
2. **Follow sibling patterns**: When creating a Svelte file, check the React and Vue equivalents for expected structure and behavior and vice-versa.
3. **Layer awareness**: Know which layer a file belongs to. Shared files affect all kits. Framework-specific files only affect that framework.
4. **Placeholder awareness**: Files in `kits/` contain `{{placeholders}}`. Files in `build/` have resolved values. The watcher handles conversion.
5. **Lint changes**: Run `composer lint` in `orchestrator` for orchestrator PHP linting. Run `composer kits:pint` for fast PHP formatting of `kits/` and `browser_tests/`. Run `composer kits:lint` to run Pint then lint/format all Inertia variants and sync changes back to `kits/`.
6. **Test after changes**: Run `composer setup && composer ci:check` inside `build/` to verify nothing is broken.
