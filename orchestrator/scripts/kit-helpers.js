#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawn } from 'child_process';

/**
 * All recognized framework flags.
 */
const FRAMEWORK_FLAGS = ['--livewire', '--react', '--svelte', '--vue'];

/**
 * All recognized variant flags.
 */
const VARIANT_FLAGS = ['--blank', '--fortify', '--workos', '--components'];

/**
 * Resolved directory paths shared across scripts.
 */
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
export const orchestratorDir = path.dirname(__dirname);
export const rootDir = path.dirname(orchestratorDir);
export const buildDir = path.join(rootDir, 'build');
export const kitsDir = path.join(rootDir, 'kits');
export const browserTestsDir = path.join(rootDir, 'browser_tests');

/**
 * ANSI color codes for terminal output.
 */
export const colors = {
    reset: '\x1b[0m',
    blue: '\x1b[34m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    dim: '\x1b[2m',
    bold: '\x1b[1m',
};

/**
 * Print a colored log line to stdout.
 */
export function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

/**
 * Parse process.argv (after slice(2)) and return the set of selected frameworks.
 * Returns null when no framework flags are present (means "run all").
 * Exits with a non-zero code if any unrecognized --* flag is found.
 */
export function parseFrameworkFlags(argv) {
    const allFlags = [...FRAMEWORK_FLAGS, ...VARIANT_FLAGS];
    const unknownFlags = argv.filter(arg => arg.startsWith('--') && !allFlags.includes(arg));

    if (unknownFlags.length > 0) {
        log(`Unknown flag(s): ${unknownFlags.join(', ')}`, 'red');
        log(`Recognized flags: ${allFlags.join(', ')}`, 'yellow');
        process.exit(1);
    }

    const selected = FRAMEWORK_FLAGS
        .filter(flag => argv.includes(flag))
        .map(flag => flag.replace('--', ''));

    return selected.length > 0 ? new Set(selected) : null;
}

/**
 * Parse process.argv (after slice(2)) and return the set of selected variant types.
 * Returns null when no variant flags are present (means "run all variants").
 */
export function parseVariantFlags(argv) {
    const selected = VARIANT_FLAGS
        .filter(flag => argv.includes(flag))
        .map(flag => flag.replace('--', ''));

    return selected.length > 0 ? new Set(selected) : null;
}

/**
 * Filter an array of variant objects by the selected frameworks and variant types.
 * Each variant must have a `framework` property (e.g. 'react', 'livewire')
 * and a `variant` property (e.g. 'blank', 'fortify', 'workos', 'components').
 * When a selection is null every variant passes for that dimension.
 */
export function filterVariants(variants, selectedFrameworks, selectedVariants = null) {
    let filtered = variants;

    if (selectedFrameworks) {
        filtered = filtered.filter(v => selectedFrameworks.has(v.framework));
    }

    if (selectedVariants) {
        filtered = filtered.filter(v => selectedVariants.has(v.variant));
    }

    return filtered;
}

/**
 * Run a command with buffered (quiet) output.
 * Both stdout and stderr are captured in memory and only printed if the
 * command exits with a non-zero code.
 * Returns a promise that resolves with { stdout, stderr } on exit 0
 * and rejects with an Error (with an `output` property) otherwise.
 */
export function runQuiet(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            stdio: ['ignore', 'pipe', 'pipe'],
            ...options,
        });

        let stdout = '';
        let stderr = '';

        child.stdout.on('data', data => {
            stdout += data.toString();
        });

        child.stderr.on('data', data => {
            stderr += data.toString();
        });

        child.on('close', code => {
            if (code === 0) {
                resolve({ stdout, stderr });

                return;
            }

            const display = [command, ...args].join(' ');
            const output = [stdout, stderr].filter(Boolean).join('\n');
            const error = new Error(`Command failed: ${display}`);
            error.output = output;
            reject(error);
        });

        child.on('error', reject);
    });
}

/**
 * Run a command with inherited stdio (verbose mode, used as fallback).
 */
export function runInherit(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            stdio: 'inherit',
            ...options,
        });

        child.on('close', code => {
            if (code === 0) {
                resolve();

                return;
            }

            const display = [command, ...args].join(' ');
            reject(new Error(`Command failed: ${display}`));
        });

        child.on('error', reject);
    });
}

/**
 * Human-readable elapsed time.
 */
function formatElapsed(ms) {
    if (ms < 1000) {
        return `${ms}ms`;
    }

    const seconds = (ms / 1000).toFixed(1);

    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = (seconds % 60).toFixed(0);

    return `${minutes}m ${remainingSeconds}s`;
}

/**
 * Print an end-of-run summary table.
 *
 * `results` is an array of { key, display, status, elapsed, reason? } objects.
 * `status` is one of 'passed', 'failed', or 'skipped'.
 */
export function printSummary(scriptLabel, results) {
    const passed = results.filter(r => r.status === 'passed');
    const failed = results.filter(r => r.status === 'failed');
    const skipped = results.filter(r => r.status === 'skipped');

    log(`\n${'─'.repeat(60)}`, 'dim');
    log(`${scriptLabel} Summary`, 'bold');
    log(`${'─'.repeat(60)}`, 'dim');

    for (const r of results) {
        const icon = r.status === 'passed' ? '✓' : r.status === 'failed' ? '✗' : '○';
        const statusColor = r.status === 'passed' ? 'green' : r.status === 'failed' ? 'red' : 'yellow';
        const elapsed = r.elapsed ? ` (${formatElapsed(r.elapsed)})` : '';
        const reason = r.reason ? ` — ${r.reason}` : '';

        log(`  ${icon} ${r.display}${elapsed}${reason}`, statusColor);
    }

    log(`${'─'.repeat(60)}`, 'dim');

    const parts = [];
    if (passed.length > 0) {
        parts.push(`${passed.length} passed`);
    }
    if (failed.length > 0) {
        parts.push(`${failed.length} failed`);
    }
    if (skipped.length > 0) {
        parts.push(`${skipped.length} skipped`);
    }

    log(`  ${parts.join(', ')}`, failed.length > 0 ? 'red' : 'green');
    log('', 'reset');
}

/**
 * Remove and recreate the build directory.
 */
export function removeBuildDirectory() {
    if (!fs.existsSync(buildDir)) {
        return;
    }

    fs.rmSync(buildDir, { recursive: true, force: true });
}

/**
 * Run the matrix loop shared by check-kits, lint-kits, and browser-tests-kits.
 *
 * @param {object}   options
 * @param {string}   options.scriptLabel     Label for summary output (e.g. 'kits:check').
 * @param {Array}    options.allVariants     Full variant list before filtering.
 * @param {Function} options.runVariant      Async (variant, index, total) => void.
 * @param {string}   [options.successVerb]   Verb for the per-variant success line (default 'Passed').
 */
export async function runMatrix({ scriptLabel, allVariants, runVariant, successVerb = 'Passed' }) {
    const argv = process.argv.slice(2);
    const selectedFrameworks = parseFrameworkFlags(argv);
    const selectedVariants = parseVariantFlags(argv);
    const active = filterVariants(allVariants, selectedFrameworks, selectedVariants);

    if (active.length === 0) {
        log('No variants matched the selected flags.', 'yellow');
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

    const skipped = allVariants.filter(v => !active.includes(v));

    for (let index = 0; index < total; index++) {
        const variant = active[index];
        const start = Date.now();

        try {
            await runVariant(variant, index + 1, total);
            results.push({ key: variant.key, display: variant.display, status: 'passed', elapsed: Date.now() - start });
            log(`  ${colors.green}✓ ${successVerb}${colors.reset}`);
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

    printSummary(scriptLabel, results);

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}
