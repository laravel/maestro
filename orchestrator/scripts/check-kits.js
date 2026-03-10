#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { colors, filterVariants, log, parseFrameworkFlags, printSummary, runQuiet } from './kit-helpers.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const buildDir = path.join(rootDir, 'build');

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
    {
        key: 'livewire-blank',
        display: 'Livewire Blank',
        framework: 'livewire',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--blank'],
    },
    {
        key: 'livewire',
        display: 'Livewire Fortify',
        framework: 'livewire',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire'],
    },
    {
        key: 'livewire-components',
        display: 'Livewire Components',
        framework: 'livewire',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--components'],
    },
    {
        key: 'livewire-workos',
        display: 'Livewire WorkOS',
        framework: 'livewire',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--workos'],
    },
];

function removeBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        return;
    }

    fs.rmSync(buildDir, { recursive: true, force: true });
}

async function checkCurrentBuild() {
    log('  Installing dependencies...', 'dim');
    await runQuiet('composer', ['setup'], { cwd: buildDir });

    log('  Running ci:check...', 'dim');
    await runQuiet('composer', ['ci:check'], { cwd: buildDir });
}

async function checkVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display}`, 'blue');

    removeBuildDirectory();

    log('  Building variant...', 'dim');
    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    await checkCurrentBuild();
}

async function main() {
    const selected = parseFrameworkFlags(process.argv.slice(2));
    const active = filterVariants(variants, selected);

    if (active.length === 0) {
        log('No variants matched the selected framework flags.', 'yellow');
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
            await checkVariant(variant, index + 1, total);
            results.push({ key: variant.key, display: variant.display, status: 'passed', elapsed: Date.now() - start });
            log(`  ${colors.green}✓ Passed${colors.reset}`);
        } catch (error) {
            results.push({ key: variant.key, display: variant.display, status: 'failed', elapsed: Date.now() - start });
            log(`  ✗ Failed: ${error.message}`, 'red');

            if (error.output) {
                log('\n--- captured output ---', 'dim');
                console.log(error.output);
                log('--- end output ---\n', 'dim');
            }

            // Continue to remaining variants
        }
    }

    for (const s of skipped) {
        results.push({ key: s.key, display: s.display, status: 'skipped', reason: 'framework not selected' });
    }

    printSummary('kits:check', results);

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}

main().catch(error => {
    log(`\nCheck kits failed: ${error.message}`, 'red');
    process.exit(1);
});
