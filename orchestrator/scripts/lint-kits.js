#!/usr/bin/env node

import {
    buildDir,
    buildJsonSummary,
    buildSkippedResults,
    ensureNoActiveWatcher,
    filterVariants,
    isJsonOutputRequested,
    log,
    orchestratorDir,
    parseFrameworkFlags,
    parseVariantFlags,
    printSummary,
    removeBuildDirectory,
    runInherit,
    runQuiet,
    writeJsonSummary,
    colors,
} from './kit-helpers.js';

/**
 * Only Inertia variants need frontend lint/format. Livewire has no frontend
 * lint phase — its PHP formatting is handled by `kits:pint` (called before
 * this script by the `kits:lint` composer command).
 */
const variants = [
    {
        key: 'react-blank',
        display: 'React Blank',
        framework: 'react',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--blank'],
    },
    {
        key: 'react',
        display: 'React Fortify',
        framework: 'react',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'react-workos',
        display: 'React WorkOS',
        framework: 'react',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos'],
    },
    {
        key: 'react-teams',
        display: 'React Teams (Fortify)',
        framework: 'react',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--teams'],
    },
    {
        key: 'react-workos-teams',
        display: 'React Teams (WorkOS)',
        framework: 'react',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos', '--teams'],
    },
    {
        key: 'svelte-blank',
        display: 'Svelte Blank',
        framework: 'svelte',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--blank'],
    },
    {
        key: 'svelte',
        display: 'Svelte Fortify',
        framework: 'svelte',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'svelte-workos',
        display: 'Svelte WorkOS',
        framework: 'svelte',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos'],
    },
    {
        key: 'svelte-teams',
        display: 'Svelte Teams (Fortify)',
        framework: 'svelte',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--teams'],
    },
    {
        key: 'svelte-workos-teams',
        display: 'Svelte Teams (WorkOS)',
        framework: 'svelte',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos', '--teams'],
    },
    {
        key: 'vue-blank',
        display: 'Vue Blank',
        framework: 'vue',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--blank'],
    },
    {
        key: 'vue',
        display: 'Vue Fortify',
        framework: 'vue',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
    {
        key: 'vue-workos',
        display: 'Vue WorkOS',
        framework: 'vue',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos'],
    },
    {
        key: 'vue-teams',
        display: 'Vue Teams (Fortify)',
        framework: 'vue',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--teams'],
    },
    {
        key: 'vue-workos-teams',
        display: 'Vue Teams (WorkOS)',
        framework: 'vue',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos', '--teams'],
    },
];

const MAX_LINT_PASSES = 2;

async function lintCurrentBuild({ jsonMode }) {
    if (!jsonMode) {
        log('  Installing composer deps...', 'dim');
    }

    await runQuiet('composer', ['install'], { cwd: buildDir });

    if (!jsonMode) {
        log('  Installing npm deps...', 'dim');
    }

    await runQuiet('npm', ['install'], { cwd: buildDir });

    if (!jsonMode) {
        log('  Building frontend...', 'dim');
    }

    await runQuiet('npm', ['run', 'build'], { cwd: buildDir });

    for (let pass = 1; pass <= MAX_LINT_PASSES; pass++) {
        if (!jsonMode) {
            log(`  Running lint pass ${pass}...`, 'dim');
        }

        await runQuiet('npm', ['run', 'lint'], { cwd: buildDir });

        if (!jsonMode) {
            log(`  Running format pass ${pass}...`, 'dim');
        }

        await runQuiet('npm', ['run', 'format'], { cwd: buildDir });
    }
}

function runWatcherInitialSync({ jsonMode }) {
    if (!jsonMode) {
        log('  Syncing changes back to kits...', 'dim');
    }

    return runQuiet('node', ['scripts/watch.js', '--initial-sync-only'], {
        cwd: orchestratorDir,
    });
}

async function lintVariant(variant, index, total, context) {
    if (!context.jsonMode) {
        log(`\n[${index}/${total}] ${variant.display}`, 'blue');
    }

    removeBuildDirectory();

    if (!context.jsonMode) {
        log('  Building variant...', 'dim');
    }

    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    await lintCurrentBuild(context);
    await runWatcherInitialSync(context);
}

async function runPint({ jsonMode }) {
    if (!jsonMode) {
        log('Running Pint on kits/ and browser_tests/...', 'blue');
    }

    const run = jsonMode ? runQuiet : runInherit;

    await run('pint', ['--parallel', '../kits'], { cwd: orchestratorDir });
    await run('pint', ['--parallel', '../browser_tests'], { cwd: orchestratorDir });
}

function failedResultError(error) {
    const result = {
        message: error.message,
    };

    if (error.output) {
        result.output = error.output;
    }

    return result;
}

function writeLintJsonSummary({ startedAt, selectedFrameworks, selectedVariants, results, message = null }) {
    writeJsonSummary(buildJsonSummary({
        scriptLabel: 'kits:lint',
        startedAt,
        finishedAt: new Date(),
        selectedFrameworks,
        selectedVariants,
        results,
        message,
    }));
}

async function main() {
    const argv = process.argv.slice(2);
    const jsonMode = isJsonOutputRequested(argv, process.env);
    const startedAt = new Date();
    const selectedFrameworks = parseFrameworkFlags(argv);
    const selectedVariants = parseVariantFlags(argv);
    const active = filterVariants(variants, selectedFrameworks, selectedVariants);
    const skipped = buildSkippedResults(variants, active);
    const results = [];
    const context = { jsonMode };

    if (active.length > 0) {
        ensureNoActiveWatcher({ scriptLabel: 'kits:lint', jsonMode, startedAt, selectedFrameworks, selectedVariants });
    }

    // Always run Pint first (it applies to all frameworks including Livewire).
    if (jsonMode) {
        const pintStart = Date.now();

        try {
            await runPint(context);
            results.push({ key: 'pint', display: 'Pint', status: 'passed', elapsed: Date.now() - pintStart });
        } catch (error) {
            results.push({ key: 'pint', display: 'Pint', status: 'failed', elapsed: Date.now() - pintStart, error: failedResultError(error) });
            results.push(...skipped);
            writeLintJsonSummary({ startedAt, selectedFrameworks, selectedVariants, results });
            process.exit(1);
        }
    } else {
        await runPint(context);
    }

    // If only --livewire was selected, there are no Inertia variants to run.
    if (active.length === 0) {
        if (jsonMode) {
            const message = selectedFrameworks && selectedFrameworks.has('livewire') && selectedFrameworks.size === 1
                ? 'Livewire has no frontend lint phase. Only the shared Pint step applies.'
                : 'No Inertia variants matched the selected flags.';

            if (selectedFrameworks && selectedFrameworks.has('livewire') && selectedFrameworks.size === 1) {
                results.push({
                    key: 'livewire-frontend-lint',
                    display: 'Livewire frontend lint',
                    framework: 'livewire',
                    status: 'skipped',
                    reason: 'Livewire has no frontend lint phase.',
                });
            }

            results.push(...skipped);
            writeLintJsonSummary({ startedAt, selectedFrameworks, selectedVariants, results, message });
            process.exit(0);
        }

        if (selectedFrameworks && selectedFrameworks.has('livewire') && selectedFrameworks.size === 1) {
            log('Livewire has no frontend lint phase. Only the shared Pint step applies.', 'yellow');
        } else {
            log('No Inertia variants matched the selected flags.', 'yellow');
        }

        process.exit(0);
    }

    const labels = [];

    if (selectedFrameworks) {
        labels.push(`kits: ${[...selectedFrameworks].join(', ')}`);
    }

    if (selectedVariants) {
        labels.push(`variants: ${[...selectedVariants].join(', ')}`);
    }

    if (!jsonMode && labels.length > 0) {
        log(`Filters — ${labels.join(' | ')}`, 'blue');
    }

    const total = active.length;

    for (let index = 0; index < total; index++) {
        const variant = active[index];
        const start = Date.now();

        try {
            await lintVariant(variant, index + 1, total, context);
            results.push({ ...variant, status: 'passed', elapsed: Date.now() - start });

            if (!jsonMode) {
                log(`  ${colors.green}✓ Finished${colors.reset}`);
            }
        } catch (error) {
            results.push({ ...variant, status: 'failed', elapsed: Date.now() - start, error: failedResultError(error) });

            if (jsonMode) {
                continue;
            }

            log(`  ✗ Failed: ${error.message}`, 'red');

            if (error.output) {
                log('\n--- captured output ---', 'dim');
                console.log(error.output);
                log('--- end output ---\n', 'dim');
            }
        }
    }

    results.push(...skipped);

    if (jsonMode) {
        writeLintJsonSummary({ startedAt, selectedFrameworks, selectedVariants, results });
    } else {
        printSummary('kits:lint', results);
    }

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}

main().catch(error => {
    log(`\nLint kits failed: ${error.message}`, 'red');
    process.exit(1);
});
