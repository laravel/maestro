#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawn, spawnSync } from 'child_process';

/**
 * All recognized framework flags.
 */
const FRAMEWORK_FLAGS = ['--api', '--livewire', '--react', '--svelte', '--vue'];

/**
 * All recognized variant flags.
 */
const VARIANT_FLAGS = ['--blank', '--fortify', '--workos', '--components', '--teams'];

/**
 * Output-mode flags that should not be treated as matrix filters.
 */
export const OUTPUT_FLAGS = ['--json'];

/**
 * Environment variables that indicate an agent prefers machine-readable output.
 */
const AGENT_OUTPUT_ENV_FLAGS = ['AI_AGENT', 'OPENCODE'];

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
const realOrchestratorDir = fs.realpathSync(orchestratorDir);
const watchScript = fs.realpathSync(path.join(orchestratorDir, 'scripts', 'watch.js'));

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
 * Determine whether JSON output should be used for this run.
 */
export function isJsonOutputRequested(argv = process.argv.slice(2), env = process.env) {
    if (argv.includes('--json')) {
        return true;
    }

    return AGENT_OUTPUT_ENV_FLAGS.some(key => {
        const value = env[key];

        if (value === undefined) {
            return false;
        }

        return !['', '0', 'false', 'no', 'off'].includes(String(value).toLowerCase());
    });
}

/**
 * Parse process.argv (after slice(2)) and return the set of selected frameworks.
 * Returns null when no framework flags are present (means "run all").
 * Exits with a non-zero code if any unrecognized --* flag is found.
 */
export function parseFrameworkFlags(argv) {
    const allFlags = [...FRAMEWORK_FLAGS, ...VARIANT_FLAGS, ...OUTPUT_FLAGS];
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

function readProcessArgs(pid) {
    try {
        return fs.readFileSync(`/proc/${pid}/cmdline`, 'utf-8').split('\0').filter(Boolean);
    } catch {
        return [];
    }
}

function readProcessCwd(pid) {
    try {
        return fs.realpathSync(`/proc/${pid}/cwd`);
    } catch {
        return null;
    }
}

function isRepoWatcherProcess(pid) {
    const cwd = readProcessCwd(pid);

    if (cwd !== realOrchestratorDir) {
        return false;
    }

    const args = readProcessArgs(pid);

    if (args.includes('--initial-sync-only')) {
        return false;
    }

    return args.some(arg => path.resolve(cwd, arg) === watchScript);
}

export function isWatcherRunning() {
    const result = spawnSync('pgrep', ['-f', 'watch.js'], { encoding: 'utf8' });

    if (result.error || result.status !== 0 || !result.stdout || result.stdout.trim().length === 0) {
        return false;
    }

    return result.stdout
        .trim()
        .split('\n')
        .some(pid => isRepoWatcherProcess(pid));
}

function watcherRunningMessage(scriptLabel) {
    return `kit:run (watcher) is running. Stop it before running ${scriptLabel}.`;
}

export function ensureNoActiveWatcher({ scriptLabel, jsonMode, startedAt, selectedFrameworks = null, selectedVariants = null }) {
    if (!isWatcherRunning()) {
        return;
    }

    const error = new Error(watcherRunningMessage(scriptLabel));

    if (jsonMode) {
        writeJsonSummary(buildJsonSummary({
            scriptLabel,
            startedAt,
            finishedAt: new Date(),
            selectedFrameworks,
            selectedVariants,
            results: [{ key: 'watcher', display: 'Watcher check', status: 'failed', elapsed: 0, error }],
        }));
    } else {
        log(`\n${error.message}`, 'red');
    }

    process.exit(1);
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

function setToArray(value) {
    return value ? [...value] : [];
}

function normalizeResult(result) {
    const normalized = {
        key: result.key,
        display: result.display,
        status: result.status,
    };

    if (result.framework) {
        normalized.framework = result.framework;
    }

    if (result.variant) {
        normalized.variant = result.variant;
    }

    if (Number.isFinite(result.elapsedMs ?? result.elapsed)) {
        normalized.elapsedMs = result.elapsedMs ?? result.elapsed;
    }

    if (result.reason) {
        normalized.reason = result.reason;
    }

    if (result.error) {
        const message = typeof result.error === 'string' ? result.error : result.error.message;

        normalized.error = {
            message,
        };

        if (typeof result.error !== 'string' && result.error.output) {
            normalized.error.output = result.error.output;
        }
    }

    return normalized;
}

/**
 * Convert matrix results into a stable machine-readable JSON summary object.
 */
export function buildJsonSummary({
    scriptLabel,
    startedAt,
    finishedAt,
    selectedFrameworks,
    selectedVariants,
    results,
    message = null,
}) {
    const started = startedAt instanceof Date ? startedAt : new Date(startedAt);
    const finished = finishedAt instanceof Date ? finishedAt : new Date(finishedAt);
    const normalizedResults = results.map(normalizeResult);
    const failed = normalizedResults.filter(result => result.status === 'failed').length;

    const summary = {
        script: scriptLabel,
        status: failed > 0 ? 'failed' : 'passed',
        startedAt: started.toISOString(),
        finishedAt: finished.toISOString(),
        elapsedMs: finished.getTime() - started.getTime(),
        jsonMode: true,
        filters: {
            frameworks: setToArray(selectedFrameworks),
            variants: setToArray(selectedVariants),
        },
        totals: {
            passed: normalizedResults.filter(result => result.status === 'passed').length,
            failed,
            skipped: normalizedResults.filter(result => result.status === 'skipped').length,
        },
        results: normalizedResults,
    };

    if (message) {
        summary.message = message;
    }

    return summary;
}

/**
 * Format a JSON summary as the exact stdout payload for JSON mode.
 */
export function formatJsonSummary(summary) {
    return `${JSON.stringify(summary, null, 2)}\n`;
}

/**
 * Write a single JSON summary object to stdout.
 */
export function writeJsonSummary(summary) {
    process.stdout.write(formatJsonSummary(summary));
}

/**
 * Build skipped result entries for every variant excluded by active filters.
 */
export function buildSkippedResults(allVariants, activeVariants, reason = 'kit not selected') {
    return allVariants
        .filter(variant => !activeVariants.includes(variant))
        .map(variant => ({
            key: variant.key,
            display: variant.display,
            framework: variant.framework,
            variant: variant.variant,
            status: 'skipped',
            reason,
        }));
}

function failedResultError(error) {
    const result = {
        message: error.message,
    };

    if (error.output) {
        result.output = error.output;
    }

    return result;
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
 * @param {boolean}  [options.guardActiveWatcher] Stop before destructive build operations if the watcher is running.
 */
export async function runMatrix({ scriptLabel, allVariants, runVariant, successVerb = 'Passed', guardActiveWatcher = false }) {
    const argv = process.argv.slice(2);
    const jsonMode = isJsonOutputRequested(argv, process.env);
    const startedAt = new Date();
    const selectedFrameworks = parseFrameworkFlags(argv);
    const selectedVariants = parseVariantFlags(argv);
    const active = filterVariants(allVariants, selectedFrameworks, selectedVariants);
    const skipped = buildSkippedResults(allVariants, active);

    if (active.length === 0) {
        const message = 'No variants matched the selected flags.';

        if (jsonMode) {
            writeJsonSummary(buildJsonSummary({
                scriptLabel,
                startedAt,
                finishedAt: new Date(),
                selectedFrameworks,
                selectedVariants,
                results: skipped,
                message,
            }));

            process.exit(0);
        }

        log(message, 'yellow');
        process.exit(0);
    }

    if (guardActiveWatcher) {
        ensureNoActiveWatcher({ scriptLabel, jsonMode, startedAt, selectedFrameworks, selectedVariants });
    }

    const labels = [];

    if (selectedFrameworks) {
        labels.push(`kits: ${[...selectedFrameworks].join(', ')}`);
    }

    if (selectedVariants) {
        labels.push(`variants: ${[...selectedVariants].join(', ')}`);
    }

    if (!jsonMode && labels.length > 0) {
        log(`Filters — ${labels.join(' | ')}`, 'blue');
    }

    const total = active.length;
    const results = [];

    for (let index = 0; index < total; index++) {
        const variant = active[index];
        const start = Date.now();

        try {
            await runVariant(variant, index + 1, total, { jsonMode });
            results.push({ ...variant, status: 'passed', elapsed: Date.now() - start });

            if (!jsonMode) {
                log(`  ${colors.green}✓ ${successVerb}${colors.reset}`);
            }
        } catch (error) {
            results.push({ ...variant, status: 'failed', elapsed: Date.now() - start, error: failedResultError(error) });

            if (jsonMode) {
                continue;
            }

            log(`  ✗ Failed: ${error.message}`, 'red');

            if (error.output) {
                log('\n--- captured output ---', 'dim');
                console.log(error.output);
                log('--- end output ---\n', 'dim');
            }
        }
    }

    results.push(...skipped);

    if (jsonMode) {
        writeJsonSummary(buildJsonSummary({
            scriptLabel,
            startedAt,
            finishedAt: new Date(),
            selectedFrameworks,
            selectedVariants,
            results,
        }));
    } else {
        printSummary(scriptLabel, results);
    }

    removeBuildDirectory();

    if (results.some(r => r.status === 'failed')) {
        process.exit(1);
    }
}
