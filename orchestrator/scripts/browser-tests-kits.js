#!/usr/bin/env node

import { execSync } from 'child_process';
import fs from 'fs';
import {
    browserTestsDir,
    buildDir,
    log,
    orchestratorDir,
    removeBuildDirectory,
    runInherit,
    runMatrix,
    runQuiet,
} from './kit-helpers.js';

const variants = [
    {
        key: 'livewire',
        display: 'Livewire',
        framework: 'livewire',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire'],
    },
    {
        key: 'react',
        display: 'React',
        framework: 'react',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'svelte',
        display: 'Svelte',
        framework: 'svelte',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'vue',
        display: 'Vue',
        framework: 'vue',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
];

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

runMatrix({
    scriptLabel: 'kits:browser-tests',
    allVariants: variants,
    runVariant: browserTestVariant,
}).catch(error => {
    log(`\nBrowser tests failed: ${error.message}`, 'red');
    process.exit(1);
});
