#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { colors, filterVariants, log, parseFrameworkFlags, printSummary, runInherit, runQuiet } from './kit-helpers.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const buildDir = path.join(rootDir, 'build');

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
        buildArgs: ['build', '--no-interaction', '--kit=React', '--blank'],
    },
    {
        key: 'react',
        display: 'React Fortify',
        framework: 'react',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'react-workos',
        display: 'React WorkOS',
        framework: 'react',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos'],
    },
    {
        key: 'svelte-blank',
        display: 'Svelte Blank',
        framework: 'svelte',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--blank'],
    },
    {
        key: 'svelte',
        display: 'Svelte Fortify',
        framework: 'svelte',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'svelte-workos',
        display: 'Svelte WorkOS',
        framework: 'svelte',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos'],
    },
    {
        key: 'vue-blank',
        display: 'Vue Blank',
        framework: 'vue',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--blank'],
    },
    {
        key: 'vue',
        display: 'Vue Fortify',
        framework: 'vue',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
    {
        key: 'vue-workos',
        display: 'Vue WorkOS',
        framework: 'vue',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos'],
    },
];

function removeBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        return;
    }

    fs.rmSync(buildDir, { recursive: true, force: true });
}

async function lintCurrentBuild() {
    log('  Installing composer deps...', 'dim');
    await runQuiet('composer', ['install'], { cwd: buildDir });

    log('  Installing npm deps...', 'dim');
    await runQuiet('npm', ['install'], { cwd: buildDir });

    log('  Building frontend...', 'dim');
    await runQuiet('npm', ['run', 'build'], { cwd: buildDir });

    log('  Running lint pass 1...', 'dim');
    await runQuiet('npm', ['run', 'lint'], { cwd: buildDir });

    log('  Running format pass 1...', 'dim');
    await runQuiet('npm', ['run', 'format'], { cwd: buildDir });

    log('  Running lint pass 2...', 'dim');
    await runQuiet('npm', ['run', 'lint'], { cwd: buildDir });

    log('  Running format pass 2...', 'dim');
    await runQuiet('npm', ['run', 'format'], { cwd: buildDir });
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
    const selected = parseFrameworkFlags(process.argv.slice(2));
    const active = filterVariants(variants, selected);

    // Always run Pint first (it applies to all frameworks including Livewire).
    await runPint();

    // If only --livewire was selected, there are no Inertia variants to run.
    if (active.length === 0) {
        if (selected && selected.has('livewire') && selected.size === 1) {
            log('Livewire has no frontend lint phase. Only the shared Pint step applies.', 'yellow');
        } else {
            log('No Inertia variants matched the selected framework flags.', 'yellow');
        }

        process.exit(0);
    }

    if (selected) {
        log(`Frameworks selected: ${[...selected].join(', ')}`, 'blue');
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
        results.push({ key: s.key, display: s.display, status: 'skipped', reason: 'framework not selected' });
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
