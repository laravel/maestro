#!/usr/bin/env node

import { spawn, execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

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
        buildArgs: ['build', '--no-interaction', '--kit=Livewire'],
    },
    {
        key: 'react',
        display: 'React',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'svelte',
        display: 'Svelte',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'vue',
        display: 'Vue',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
];

const colors = {
    reset: '\x1b[0m',
    blue: '\x1b[34m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function runCommand(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            stdio: 'inherit',
            shell: true,
            ...options,
        });

        child.on('close', code => {
            if (code === 0) {
                resolve();

                return;
            }

            reject(new Error(`Command failed: ${command} ${args.join(' ')}`));
        });

        child.on('error', reject);
    });
}

function removeBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        return;
    }

    log('Removing existing build directory...', 'yellow');
    fs.rmSync(buildDir, { recursive: true, force: true });
}

function copyBrowserTests() {
    log('Copying browser tests into build...', 'blue');
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
        log('Playwright browsers already installed, skipping...', 'green');

        return;
    }

    log('Installing Playwright browsers...', 'blue');
    await runCommand('npx', ['playwright', 'install', '--with-deps'], { cwd: buildDir });
}

async function runBrowserTestsForCurrentBuild() {
    await runCommand('composer', ['remove', '--dev', 'phpunit/phpunit', '--no-interaction', '--no-update'], { cwd: buildDir });
    await runCommand('composer', ['require', '--dev', 'pestphp/pest', 'pestphp/pest-plugin-browser', 'pestphp/pest-plugin-laravel', '--no-interaction'], { cwd: buildDir });
    await runCommand('npm', ['install'], { cwd: buildDir });
    await runCommand('npm', ['install', 'playwright'], { cwd: buildDir });
    await ensurePlaywrightBrowsers();
    await runCommand('cp', ['.env.example', '.env'], { cwd: buildDir });
    await runCommand('php', ['artisan', 'key:generate'], { cwd: buildDir });
    await runCommand('npm', ['run', 'build'], { cwd: buildDir });
    await runCommand('php', ['vendor/bin/pest', '--parallel'], { cwd: buildDir });
}

async function browserTestVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display} (${variant.key})`, 'blue');

    removeBuildDirectory();

    log('Building variant...', 'blue');
    await runCommand('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    copyBrowserTests();

    log('Running browser tests...', 'blue');
    await runBrowserTestsForCurrentBuild();

    log(`Passed ${variant.key}`, 'green');
}

async function main() {
    const total = variants.length;

    for (let index = 0; index < total; index++) {
        await browserTestVariant(variants[index], index + 1, total);
    }

    log('\nAll starter kit variants passed browser tests.', 'green');
}

main().catch(error => {
    log(`\nBrowser tests failed: ${error.message}`, 'red');
    process.exit(1);
});
