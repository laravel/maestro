#!/usr/bin/env node

import { spawn } from 'child_process';

/**
 * All recognized framework flags.
 */
const FRAMEWORK_FLAGS = ['--livewire', '--react', '--svelte', '--vue'];

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
 */
export function parseFrameworkFlags(argv) {
    const selected = FRAMEWORK_FLAGS
        .filter(flag => argv.includes(flag))
        .map(flag => flag.replace('--', ''));

    return selected.length > 0 ? new Set(selected) : null;
}

/**
 * Filter an array of variant objects by the selected frameworks.
 * Each variant must have a `framework` property (e.g. 'react', 'livewire').
 * When `selected` is null every variant passes.
 */
export function filterVariants(variants, selected) {
    if (!selected) {
        return variants;
    }

    return variants.filter(v => selected.has(v.framework));
}

/**
 * Run a command with buffered (quiet) output.
 * - stdout/stderr are captured and only printed if the command fails.
 * - stderr is always forwarded in real-time so warnings are visible.
 * Returns a promise that resolves on exit 0 and rejects otherwise.
 */
export function runQuiet(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            stdio: ['ignore', 'pipe', 'pipe'],
            shell: true,
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

            const output = [stdout, stderr].filter(Boolean).join('\n');
            const error = new Error(`Command failed: ${command} ${args.join(' ')}`);
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
