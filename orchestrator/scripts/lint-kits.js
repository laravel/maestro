#!/usr/bin/env node

import { spawn } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const buildDir = path.join(rootDir, 'build');

const variants = [
    {
        key: 'react-blank',
        display: 'React Blank',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--blank'],
    },
    {
        key: 'react',
        display: 'React Fortify',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'react-workos',
        display: 'React WorkOS',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos'],
    },
    {
        key: 'svelte-blank',
        display: 'Svelte Blank',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--blank'],
    },
    {
        key: 'svelte',
        display: 'Svelte Fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'svelte-workos',
        display: 'Svelte WorkOS',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos'],
    },
    {
        key: 'vue-blank',
        display: 'Vue Blank',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--blank'],
    },
    {
        key: 'vue',
        display: 'Vue Fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
    {
        key: 'vue-workos',
        display: 'Vue WorkOS',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos'],
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

async function lintCurrentBuild() {
    await runCommand('npm', ['install'], { cwd: buildDir });
    await runCommand('npm', ['run', 'lint'], { cwd: buildDir });
    await runCommand('npm', ['run', 'format'], { cwd: buildDir });
    await runCommand('npm', ['run', 'lint'], { cwd: buildDir });
    await runCommand('npm', ['run', 'format'], { cwd: buildDir });
}

function runWatcherInitialSync() {
    return runCommand('node', ['scripts/watch.js', '--initial-sync-only'], {
        cwd: orchestratorDir,
    });
}

async function lintVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display} (${variant.key})`, 'blue');

    removeBuildDirectory();

    log('Building variant...', 'blue');
    await runCommand('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    log('Running frontend lint and format commands...', 'blue');
    await lintCurrentBuild();

    log('Running watcher initial sync...', 'blue');
    await runWatcherInitialSync();

    log(`Finished ${variant.key}`, 'green');
}

async function main() {
    const total = variants.length;

    for (let index = 0; index < total; index++) {
        await lintVariant(variants[index], index + 1, total);
    }

    log('\nAll Inertia kit variants linted successfully.', 'green');
}

main().catch(error => {
    log(`\nLint kits failed: ${error.message}`, 'red');
    process.exit(1);
});
