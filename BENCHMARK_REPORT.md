# Benchmark Report: Vite Plus vs Main

Comparison of `npm run build` and `npm run check` performance between `feat/vite-plus` (Vite Plus) and `main` (Vite + ESLint + Prettier).

## Per Variant

| Kit | Build main (ms) | Build vite-plus (ms) | Build Delta | Check main (ms) | Check vite-plus (ms) | Check Delta |
|-----|-----------------|---------------------|-------------|-----------------|---------------------|-------------|
| react-blank | 707 | 854 | +147 (+20,8%) | 1174 | 1168 | -6 (-0,5%) |
| react | 1538 | 1706 | +168 (+10,9%) | 2051 | 1730 | -321 (-15,7%) |
| react-workos | 1252 | 1338 | +86 (+6,9%) | 1620 | 1484 | -136 (-8,4%) |
| svelte-blank | 631 | 775 | +144 (+22,8%) | 1088 | 1324 | +236 (+21,7%) |
| svelte | 2246 | 2320 | +74 (+3,3%) | 1812 | 1341 | -471 (-26,0%) |
| svelte-workos | 2088 | 2248 | +160 (+7,7%) | 1541 | 1332 | -209 (-13,6%) |
| vue-blank | 566 | 663 | +97 (+17,1%) | 1406 | 1274 | -132 (-9,4%) |
| vue | 1030 | 1234 | +204 (+19,8%) | 2371 | 1434 | -937 (-39,5%) |
| vue-workos | 886 | 1080 | +194 (+21,9%) | 2045 | 1431 | -614 (-30,0%) |
| livewire-blank | 309 | 393 | +84 (+27,2%) | N/A | N/A | N/A |
| livewire | 397 | 502 | +105 (+26,4%) | N/A | N/A | N/A |
| livewire-components | 403 | 482 | +79 (+19,6%) | N/A | N/A | N/A |
| livewire-workos | 396 | 481 | +85 (+21,5%) | N/A | N/A | N/A |

## Per Stack

| Stack | Build main (ms) | Build vite-plus (ms) | Build Delta | Check main (ms) | Check vite-plus (ms) | Check Delta |
|-------|-----------------|---------------------|-------------|-----------------|---------------------|-------------|
| React | 1165 | 1299 | +134 (+11,5%) | 1615 | 1460 | -155 (-9,6%) |
| Svelte | 1655 | 1781 | +126 (+7,6%) | 1480 | 1332 | -148 (-10,0%) |
| Vue | 827 | 992 | +165 (+20,0%) | 1941 | 1380 | -561 (-28,9%) |
| Livewire | 376 | 465 | +89 (+23,7%) | N/A | N/A | N/A |

## Overall

| Metric | main (ms) | vite-plus (ms) | Delta |
|--------|----------|---------------|-------|
| Build | 957 | 1083 | +126 (+13,2%) |
| Check (Inertia only) | 1679 | 1391 | -288 (-17,2%) |

## Build Overhead Analysis

The build slowdown is **not** caused by Vite itself being slower. It is a fixed startup overhead from the `vp` CLI wrapper. An isolated test on the same project (Vue Blank, feat/vite-plus branch) running `vp build` vs `vite build` directly confirms this:

| Command | Run 1 | Run 2 | Run 3 | Run 4 | Run 5 | Avg |
|---------|-------|-------|-------|-------|-------|-----|
| `vp build` | 487ms | 443ms | 459ms | 442ms | 443ms | ~455ms |
| `vite build` | 384ms | 374ms | 363ms | 368ms | 358ms | ~369ms |

The `vp` binary adds a consistent ~80ms startup cost to load and parse the extended config (lint/fmt sections in `vite.config.ts`), even though those sections are not used during build. The actual Vite build pipeline is unchanged.

