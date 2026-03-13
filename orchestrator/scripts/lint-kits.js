#!/usr/bin/env node

import {
    buildDir,
    filterVariants,
    log,
    orchestratorDir,
    parseFrameworkFlags,
    parseVariantFlags,
    printSummary,
    removeBuildDirectory,
    runInherit,
    runQuiet,
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
];

const MAX_LINT_PASSES = 2;

async function lintCurrentBuild() {
    log('  Installing composer deps...', 'dim');
    await runQuiet('composer', ['install'], { cwd: buildDir });

    log('  Installing npm deps...', 'dim');
    await runQuiet('npm', ['install'], { cwd: buildDir });

    log('  Building frontend...', 'dim');
    await runQuiet('npm', ['run', 'build'], { cwd: buildDir });

    for (let pass = 1; pass <= MAX_LINT_PASSES; pass++) {
        log(`  Running lint pass ${pass}...`, 'dim');
        await runQuiet('npm', ['run', 'lint'], { cwd: buildDir });

        log(`  Running format pass ${pass}...`, 'dim');
        await runQuiet('npm', ['run', 'format'], { cwd: buildDir });
    }
}

function runWatcherInitialSync() {
    log('  Syncing changes back to kits...', 'dim');

    return runQuiet('node', ['scripts/watch.js', '--initial-sync-only'], {
        cwd: orchestratorDir,
    });
}

async function lintVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display}`, 'blue');

    removeBuildDirectory();

    log('  Building variant...', 'dim');
    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    await lintCurrentBuild();
    await runWatcherInitialSync();
}

async function runPint() {
    log('Running Pint on kits/ and browser_tests/...', 'blue');
    await runInherit('pint', ['--parallel', '../kits'], { cwd: orchestratorDir });
    await runInherit('pint', ['--parallel', '../browser_tests'], { cwd: orchestratorDir });
}

async function main() {
    const argv = process.argv.slice(2);
    const selectedFrameworks = parseFrameworkFlags(argv);
    const selectedVariants = parseVariantFlags(argv);
    const active = filterVariants(variants, selectedFrameworks, selectedVariants);

    // Always run Pint first (it applies to all frameworks including Livewire).
    await runPint();

    // If only --livewire was selected, there are no Inertia variants to run.
    if (active.length === 0) {
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

    if (labels.length > 0) {
        log(`Filters — ${labels.join(' | ')}`, 'blue');
    }

    const total = active.length;
    const results = [];

    // Track skipped variants for summary
    const skipped = variants.filter(v => !active.includes(v));

    for (let index = 0; index < total; index++) {
        const variant = active[index];
        const start = Date.now();

        try {
            await lintVariant(variant, index + 1, total);
            results.push({ key: variant.key, display: variant.display, status: 'passed', elapsed: Date.now() - start });
            log(`  ${colors.green}✓ Finished${colors.reset}`);
        } catch (error) {
            results.push({ key: variant.key, display: variant.display, status: 'failed', elapsed: Date.now() - start });
            log(`  ✗ Failed: ${error.message}`, 'red');

            if (error.output) {
                log('\n--- captured output ---', 'dim');
                console.log(error.output);
                log('--- end output ---\n', 'dim');
            }
        }
    }

    for (const s of skipped) {
        results.push({ key: s.key, display: s.display, status: 'skipped', reason: 'kit not selected' });
    }

    printSummary('kits:lint', results);

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}

main().catch(error => {
    log(`\nLint kits failed: ${error.message}`, 'red');
    process.exit(1);
});
