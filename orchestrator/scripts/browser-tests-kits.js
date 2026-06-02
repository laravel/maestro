#!/usr/bin/env node

import { execSync, spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
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
    {
        key: 'livewire-teams',
        display: 'Livewire Teams',
        framework: 'livewire',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--teams'],
    },
    {
        key: 'react-teams',
        display: 'React Teams',
        framework: 'react',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--teams'],
    },
    {
        key: 'svelte-teams',
        display: 'Svelte Teams',
        framework: 'svelte',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--teams'],
    },
    {
        key: 'vue-teams',
        display: 'Vue Teams',
        framework: 'vue',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--teams'],
    },
];

function runCapturedToFile(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const outputPath = path.join(options.cwd ?? process.cwd(), `.browser-tests-output-${Date.now()}.log`);
        const output = fs.openSync(outputPath, 'w');
        let outputRead = false;
        const child = spawn(command, args, {
            ...options,
            stdio: ['ignore', output, output],
        });

        const readOutput = () => {
            if (outputRead) {
                return '';
            }

            outputRead = true;
            fs.closeSync(output);

            const captured = fs.readFileSync(outputPath, 'utf-8');
            fs.rmSync(outputPath, { force: true });

            return captured;
        };

        child.on('close', code => {
            const captured = readOutput();

            if (code === 0) {
                resolve({ stdout: captured, stderr: '' });

                return;
            }

            const display = [command, ...args].join(' ');
            const error = new Error(`Command failed: ${display}`);
            error.output = captured;
            reject(error);
        });

        child.on('error', error => {
            error.output = readOutput();
            reject(error);
        });
    });
}

function prepareBrowserTests(variant, { jsonMode }) {
    if (!jsonMode) {
        log('  Copying browser test bootstrap into build...', 'dim');
    }

    fs.cpSync(path.join(browserTestsDir, 'bootstrap'), buildDir, { recursive: true });

    const suite = variant === 'teams' ? 'teams' : 'common';

    if (!jsonMode) {
        log(`  Copying ${suite} browser tests into build...`, 'dim');
    }

    fs.cpSync(path.join(browserTestsDir, suite), buildDir, { recursive: true });
}

function playwrightBrowsersInstalled() {
    try {
        const output = execSync('npx playwright install chromium --only-shell --dry-run', { cwd: buildDir, encoding: 'utf-8', stdio: ['pipe', 'pipe', 'pipe'] });
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

async function ensurePlaywrightBrowsers({ jsonMode }) {
    if (playwrightBrowsersInstalled()) {
        if (!jsonMode) {
            log('  Playwright browsers already installed, skipping...', 'dim');
        }

        return;
    }

    if (!jsonMode) {
        log('  Installing Playwright browsers...', 'blue');
    }

    const run = jsonMode ? runQuiet : runInherit;

    await run('npx', ['playwright', 'install', 'chromium', '--with-deps', '--only-shell'], { cwd: buildDir });
}

async function runBrowserTestsForCurrentBuild(context) {
    if (!context.jsonMode) {
        log('  Configuring Pest + Playwright deps...', 'dim');
    }

    await runQuiet('composer', ['remove', '--dev', 'phpunit/phpunit', '--no-interaction', '--no-update'], { cwd: buildDir });
    await runQuiet('composer', ['require', '--dev', 'pestphp/pest', 'pestphp/pest-plugin-browser', 'pestphp/pest-plugin-laravel', '--no-interaction'], { cwd: buildDir });

    if (!context.jsonMode) {
        log('  Installing npm deps...', 'dim');
    }

    await runQuiet('npm', ['install'], { cwd: buildDir });
    await runQuiet('npm', ['install', 'playwright'], { cwd: buildDir });

    await ensurePlaywrightBrowsers(context);

    if (!context.jsonMode) {
        log('  Preparing environment...', 'dim');
    }

    await runQuiet('cp', ['.env.example', '.env'], { cwd: buildDir });
    await runQuiet('php', ['artisan', 'key:generate'], { cwd: buildDir });

    if (!context.jsonMode) {
        log('  Building frontend...', 'dim');
    }

    await runQuiet('npm', ['run', 'build'], { cwd: buildDir });

    if (!context.jsonMode) {
        log('  Running browser tests...', 'blue');
    }

    const run = context.jsonMode ? runCapturedToFile : runInherit;

    await run('php', ['vendor/bin/pest', '--parallel'], { cwd: buildDir });
}

async function browserTestVariant(variant, index, total, context) {
    if (!context.jsonMode) {
        log(`\n[${index}/${total}] ${variant.display}`, 'blue');
    }

    removeBuildDirectory();

    if (!context.jsonMode) {
        log('  Building variant...', 'dim');
    }

    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    prepareBrowserTests(variant.variant, context);

    await runBrowserTestsForCurrentBuild(context);
}

runMatrix({
    scriptLabel: 'kits:browser-tests',
    allVariants: variants,
    runVariant: browserTestVariant,
}).catch(error => {
    log(`\nBrowser tests failed: ${error.message}`, 'red');
    process.exit(1);
});
