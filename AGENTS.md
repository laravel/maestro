# Maestro

Maestro is the monorepo orchestrator for the official Laravel starter kits. Use the local maestro skill before making changes:

- **Skill**: `.claude/skills/maestro/SKILL.md` — project structure, commands, kit inheritance, placeholder system, and workflow rules.

## Quick Reference

- Build: `cd orchestrator && php artisan build` (interactive) or use `--kit`, `--blank`, `--workos`, flags
- Dev: `composer kit:run` (from `orchestrator/`)
- Test: `composer setup && composer ci:check` (from `build/`)
- Test all kits: `composer kits:check` (from `orchestrator/`) — builds and runs CI checks for all 13 variants
- Lint: `composer kits:lint` (from `orchestrator/`)
- Browser tests (all kits): `composer kits:browser-tests` (from `orchestrator/`)
- Edit in `build/` (when available), commit in `kits/`
