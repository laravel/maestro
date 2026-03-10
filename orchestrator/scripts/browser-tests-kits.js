#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { colors, filterVariants, log, parseFrameworkFlags, printSummary, runInherit, runQuiet } from './kit-helpers.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const buildDir = path.join(rootDir, 'build');
const browserTestsDir = path.join(rootDir, 'browser_tests');

const variants = [
    {
        key: 'livewire',
        display: 'Livewire',
        framework: 'livewire',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire'],
    },
    {
        key: 'react',
        display: 'React',
        framework: 'react',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'svelte',
        display: 'Svelte',
        framework: 'svelte',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'vue',
        display: 'Vue',
        framework: 'vue',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
];

function removeBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        return;
    }

    fs.rmSync(buildDir, { recursive: true, force: true });
}

function copyBrowserTests() {
    log('  Copying browser tests into build...', 'dim');
    fs.cpSync(browserTestsDir, buildDir, { recursive: true });
}

function playwrightBrowsersInstalled() {
    try {
        const output = execSync('npx playwright install --dry-run', { cwd: buildDir, encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] });
        const installPaths = output.match(/Install location:\s+(.+)/g);

        if (!installPaths) {
            return false;
        }

        return installPaths
            .map(line => line.replace('Install location:', '').trim())
            .every(dir => fs.existsSync(dir));
    } catch {
        return false;
    }
}

async function ensurePlaywrightBrowsers() {
    if (playwrightBrowsersInstalled()) {
        log('  Playwright browsers already installed, skipping...', 'dim');

        return;
    }

    log('  Installing Playwright browsers...', 'blue');
    await runInherit('npx', ['playwright', 'install', '--with-deps'], { cwd: buildDir });
}

async function runBrowserTestsForCurrentBuild() {
    log('  Configuring Pest + Playwright deps...', 'dim');
    await runQuiet('composer', ['remove', '--dev', 'phpunit/phpunit', '--no-interaction', '--no-update'], { cwd: buildDir });
    await runQuiet('composer', ['require', '--dev', 'pestphp/pest', 'pestphp/pest-plugin-browser', 'pestphp/pest-plugin-laravel', '--no-interaction'], { cwd: buildDir });

    log('  Installing npm deps...', 'dim');
    await runQuiet('npm', ['install'], { cwd: buildDir });
    await runQuiet('npm', ['install', 'playwright'], { cwd: buildDir });

    await ensurePlaywrightBrowsers();

    log('  Preparing environment...', 'dim');
    await runQuiet('cp', ['.env.example', '.env'], { cwd: buildDir });
    await runQuiet('php', ['artisan', 'key:generate'], { cwd: buildDir });

    log('  Building frontend...', 'dim');
    await runQuiet('npm', ['run', 'build'], { cwd: buildDir });

    log('  Running browser tests...', 'blue');
    await runInherit('php', ['vendor/bin/pest', '--parallel'], { cwd: buildDir });
}

async function browserTestVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display}`, 'blue');

    removeBuildDirectory();

    log('  Building variant...', 'dim');
    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    copyBrowserTests();

    await runBrowserTestsForCurrentBuild();
}

async function main() {
    const selected = parseFrameworkFlags(process.argv.slice(2));
    const active = filterVariants(variants, selected);

    if (active.length === 0) {
        log('No variants matched the selected kit flags.', 'yellow');
        process.exit(0);
    }

    if (selected) {
        log(`Kits selected: ${[...selected].join(', ')}`, 'blue');
    }

    const total = active.length;
    const results = [];

    // Track skipped variants for summary
    const skipped = variants.filter(v => !active.includes(v));

    for (let index = 0; index < total; index++) {
        const variant = active[index];
        const start = Date.now();

        try {
            await browserTestVariant(variant, index + 1, total);
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
        }
    }

    for (const s of skipped) {
        results.push({ key: s.key, display: s.display, status: 'skipped', reason: 'kit not selected' });
    }

    printSummary('kits:browser-tests', results);

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}

main().catch(error => {
    log(`\nBrowser tests failed: ${error.message}`, 'red');
    process.exit(1);
});
