#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { buildDir, log, orchestratorDir, runInherit } from './kit-helpers.js';

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

    log('Running composer setup...', 'blue');
    await runInherit('composer', ['setup'], { cwd: buildDir });

    configureEnv();

    log('Starting development server...', 'green');
    await runInherit('composer', ['dev'], { cwd: buildDir });
}

main().catch(error => {
    log(error.message, 'red');
    process.exit(1);
});
