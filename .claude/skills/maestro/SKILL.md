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

| Command                                     | Description                                                              |
|---------------------------------------------|--------------------------------------------------------------------------|
| `php artisan build`                         | Build a starter kit into `build/`. Interactive or use flags (see below). |
| `php artisan build --kit=vue`               | Build Vue Fortify kit directly.                                          |
| `php artisan build --kit=svelte --blank`    | Build Blank Svelte kit.                                                  |
| `php artisan build --kit=livewire --workos` | Build Livewire WorkOS kit.                                               |
| `composer kit:run`                          | Start dev server + file watcher (syncs `build/` back to `kits/`).        |
| `npm run watch:kits`                        | Run only the file watcher (no dev server).                               |
| `composer setup && php artisan test`        | Run inside `build/` after building to install deps and run tests.        |

Available `--kit` values defined in `orchestrator/app/Enums/StarterKit.php`.

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
│   └── WorkOS/      # WorkOS routes, migrations, config
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
│   └── WorkOS/
│       ├── Base/          # WorkOS backend
│       ├── React/         # React WorkOS pages
│       ├── Svelte/        # Svelte WorkOS pages
│       └── Vue/           # Vue WorkOS pages
│
└── Livewire/
    ├── Blank/
    ├── Base/
    ├── Fortify/
    ├── Components/        # Multi-file Blade components variant
    ├── WorkOS/
    └── Teams/
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
4. **Test**: Inside `build/`, run `composer setup && php artisan test`
5. **Commit**: Commit the changes in `kits/` (not `build/`)
6. **PR**: Create PR; after merge, Maestro auto-creates PRs for affected kit repos

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
5. **Lint changes**: For all kits run `composer lint`. For Inertia ones, also run `npm run lint` and `npm run format`. For Svelte one also run `npm run check`. All of these should be run inside `build/`
6. **Test after changes**: Run `php artisan test` inside `build/` to verify nothing is broken.
