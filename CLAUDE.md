# Maestro

Maestro is the monorepo orchestrator for the official Laravel starter kits. Use the local maestro skill before making changes:

- **Skill**: `.claude/skills/maestro/SKILL.md` — project structure, commands, kit inheritance, placeholder system, and workflow rules.

## Quick Reference

- Build: `cd orchestrator && php artisan build` (interactive) or use `--kit`, `--blank`, `--workos`, flags
- Dev: `composer kit:run` (from `orchestrator/`)
- Test: `composer setup && composer ci:check` (from `build/`)
- Test all kits: `composer kits:check` (from `orchestrator/`) — builds and runs CI checks for all 13 variants
- Pint only: `composer kits:pint` (from `orchestrator/`) — runs Pint on `kits/` and `browser_tests/`
- Lint: `composer kits:lint` (from `orchestrator/`) — runs `kits:pint`, then frontend lint/format for Inertia variants
- Browser tests (all kits): `composer kits:browser-tests` (from `orchestrator/`)
- Edit in `build/` (when available), commit in `kits/`

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
