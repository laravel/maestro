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

const colors = {
    reset: '\x1b[0m',
    blue: '\x1b[34m',
    green: '\x1b[32m',
    red: '\x1b[31m',
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

/**
 * Run a command and return a promise.
 */
function runCommand(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const fullCommand = [command, ...args].join(' ');

        const proc = spawn(fullCommand, [], {
            stdio: 'inherit',
            shell: true,
            ...options,
        });

        proc.on('close', code => {
            if (code === 0) {
                resolve();
            } else {
                reject(new Error(`Command failed with code ${code}`));
            }
        });

        proc.on('error', reject);
    });
}

function readEnvValue(envPath, key) {
    if (!fs.existsSync(envPath)) {
        return null;
    }

    const content = fs.readFileSync(envPath, 'utf-8');
    const match = content.match(new RegExp(`^${key}=(.*)$`, 'm'));

    return match ? match[1] : null;
}

function updateEnvValue(envPath, key, value) {
    if (!fs.existsSync(envPath)) {
        return;
    }

    let content = fs.readFileSync(envPath, 'utf-8');
    const regex = new RegExp(`^${key}=.*$`, 'm');

    if (regex.test(content)) {
        content = content.replace(regex, `${key}=${value}`);
        fs.writeFileSync(envPath, content);
    }
}

/**
 * Configure the .env file in the build directory.
 */
function configureEnv() {
    const buildEnvPath = path.join(buildDir, '.env');
    const orchestratorEnvPath = path.join(orchestratorDir, '.env');

    if (!fs.existsSync(buildEnvPath)) {
        return;
    }

    updateEnvValue(buildEnvPath, 'APP_URL', 'http://localhost:8000');

    if (fs.existsSync(orchestratorEnvPath)) {
        const workosClientId = readEnvValue(orchestratorEnvPath, 'WORKOS_CLIENT_ID');
        const workosApiKey = readEnvValue(orchestratorEnvPath, 'WORKOS_API_KEY');

        if (workosClientId && workosApiKey) {
            log('Copying WorkOS credentials from orchestrator .env...', 'blue');
            updateEnvValue(buildEnvPath, 'WORKOS_CLIENT_ID', workosClientId);
            updateEnvValue(buildEnvPath, 'WORKOS_API_KEY', workosApiKey);
        }
    }
}

async function main() {
    if (!fs.existsSync(buildDir)) {
        log("The build folder does not exist. Please run 'php artisan build' first.", 'red');
        process.exit(1);
    }

    process.chdir(buildDir);

    log('Running composer setup...', 'blue');
    await runCommand('composer', ['setup']);

    configureEnv();

    log('Starting development server...', 'green');
    await runCommand('composer', ['dev']);
}

main().catch(error => {
    log(error.message, 'red');
    process.exit(1);
});
