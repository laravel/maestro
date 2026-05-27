#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { pathToFileURL } from 'node:url';

import { kitsDir, log, runQuiet } from './kit-helpers.js';

const upstreamRepository = 'https://github.com/laravel/laravel';

const allowedRootDirectories = new Set([
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'routes',
    'storage',
    'tests',
]);

const allowedRootFiles = new Set([
    '.editorconfig',
    '.env.example',
    '.npmrc',
    'artisan',
    'phpunit.xml',
    'pint.json',
]);

const ignoredRootFiles = new Set([
    '.gitignore',
    '.gitattributes',
    'README.md',
    'composer.lock',
    'package-lock.json',
    'pnpm-lock.yaml',
    'yarn.lock',
    'bun.lock',
    'bun.lockb',
    'npm-shrinkwrap.json',
]);

const ignoredPathSegments = new Set([
    '.git',
    'node_modules',
    'vendor',
]);

const ignoredPathPrefixes = [
    'public/build/',
];

const appServiceProviderPath = 'app/Providers/AppServiceProvider.php';

const newFileBlockedPrefixes = [
    'app/Models/',
    'bootstrap/',
    'routes/',
    'tests/',
];

function pathSegments(relativePath) {
    return relativePath.split(/[\\/]+/).filter(Boolean);
}

function toRelativePath(parent, child) {
    return path.relative(parent, child).split(path.sep).join('/');
}

function assertSafeRelativePath(relativePath) {
    if (!relativePath || path.isAbsolute(relativePath)) {
        throw new Error(`Refusing to sync unsafe path outside kits/Shared/Blank: ${relativePath}`);
    }

    if (pathSegments(relativePath).includes('..')) {
        throw new Error(`Refusing to sync unsafe path outside kits/Shared/Blank: ${relativePath}`);
    }
}

function isIgnoredPath(relativePath) {
    const segments = pathSegments(relativePath);

    return ignoredRootFiles.has(relativePath)
        || ignoredPathPrefixes.some(prefix => relativePath.startsWith(prefix))
        || segments.some(segment => ignoredPathSegments.has(segment));
}

function extractConfigureDefaultsBlock(contents) {
    const methodIndex = contents.indexOf('    protected function configureDefaults(): void');

    if (methodIndex === -1) {
        return null;
    }

    const docblockIndex = contents.lastIndexOf('\n    /**', methodIndex);
    const start = docblockIndex === -1 ? methodIndex : docblockIndex;
    const end = contents.lastIndexOf('\n}');

    if (end === -1 || end <= start) {
        return null;
    }

    return contents.slice(start, end);
}

function ensureUseStatement(contents, useStatement) {
    if (contents.includes(`${useStatement}\n`)) {
        return contents;
    }

    const useMatches = [...contents.matchAll(/^use .+;$/gm)];

    if (useMatches.length > 0) {
        const lastUse = useMatches[useMatches.length - 1];
        const insertAt = lastUse.index + lastUse[0].length;

        return `${contents.slice(0, insertAt)}\n${useStatement}${contents.slice(insertAt)}`;
    }

    return contents.replace(/^(namespace .+;\n)/m, `$1\n${useStatement}\n`);
}

function ensureBootCallsConfigureDefaults(contents) {
    if (contents.includes('$this->configureDefaults();')) {
        return contents;
    }

    return contents.replace(
        /(public function boot\(\): void\n    \{\n)([\s\S]*?)(\n    \})/,
        (match, opening, body, closing) => {
            if (body.trim() === '//') {
                return `${opening}        $this->configureDefaults();${closing}`;
            }

            return `${opening}        $this->configureDefaults();\n${body}${closing}`;
        }
    );
}

function appendConfigureDefaults(contents, configureDefaultsBlock) {
    if (!configureDefaultsBlock || contents.includes('function configureDefaults(): void')) {
        return contents;
    }

    return contents.replace(/\n\}\s*$/, `\n${configureDefaultsBlock}\n}\n`);
}

function mergeAppServiceProvider(sourceContents, destinationContents) {
    const configureDefaultsBlock = extractConfigureDefaultsBlock(destinationContents);

    if (!configureDefaultsBlock) {
        return sourceContents;
    }

    let merged = sourceContents;

    for (const useStatement of [
        'use Carbon\\CarbonImmutable;',
        'use Illuminate\\Support\\Facades\\Date;',
        'use Illuminate\\Support\\Facades\\DB;',
        'use Illuminate\\Validation\\Rules\\Password;',
    ]) {
        merged = ensureUseStatement(merged, useStatement);
    }

    merged = ensureBootCallsConfigureDefaults(merged);

    return appendConfigureDefaults(merged, configureDefaultsBlock);
}

async function fileExists(file) {
    try {
        await fs.access(file);

        return true;
    } catch (error) {
        if (error.code === 'ENOENT') {
            return false;
        }

        throw error;
    }
}

async function blocksNewFile(destinationPath, relativePath) {
    if (!newFileBlockedPrefixes.some(prefix => relativePath.startsWith(prefix))) {
        return false;
    }

    return !await fileExists(destinationPath);
}

function isAllowedPath(relativePath) {
    const segments = pathSegments(relativePath);

    if (segments.length === 1) {
        return allowedRootFiles.has(relativePath);
    }

    return allowedRootDirectories.has(segments[0]);
}

function resolveInside(rootDir, relativePath) {
    assertSafeRelativePath(relativePath);

    const root = path.resolve(rootDir);
    const resolved = path.resolve(root, ...pathSegments(relativePath));
    const rootPrefix = root.endsWith(path.sep) ? root : `${root}${path.sep}`;

    if (resolved !== root && !resolved.startsWith(rootPrefix)) {
        throw new Error(`Refusing to sync unsafe path outside kits/Shared/Blank: ${relativePath}`);
    }

    return resolved;
}

async function collectAllowedFiles(sourceDir) {
    const files = [];

    async function walk(currentDir) {
        const entries = await fs.readdir(currentDir, { withFileTypes: true });

        for (const entry of entries) {
            const entryPath = path.join(currentDir, entry.name);
            const relativePath = toRelativePath(sourceDir, entryPath);

            if (isIgnoredPath(relativePath)) {
                continue;
            }

            if (entry.isDirectory()) {
                if (isAllowedPath(`${relativePath}/placeholder`) || isAllowedPath(relativePath)) {
                    await walk(entryPath);
                }

                continue;
            }

            if (!entry.isFile() || !isAllowedPath(relativePath)) {
                continue;
            }

            files.push(relativePath);
        }
    }

    await walk(sourceDir);

    return files.sort((a, b) => a.localeCompare(b));
}

export async function syncAllowedFile({ sourceDir, destinationDir, relativePath }) {
    assertSafeRelativePath(relativePath);

    if (isIgnoredPath(relativePath) || !isAllowedPath(relativePath)) {
        return 'skipped';
    }

    const sourcePath = resolveInside(sourceDir, relativePath);
    const destinationPath = resolveInside(destinationDir, relativePath);

    if (await blocksNewFile(destinationPath, relativePath)) {
        return 'skipped';
    }

    let sourceContents = await fs.readFile(sourcePath);

    let existingContents = null;

    try {
        existingContents = await fs.readFile(destinationPath);
    } catch (error) {
        if (error.code !== 'ENOENT') {
            throw error;
        }
    }

    if (relativePath === appServiceProviderPath && existingContents) {
        sourceContents = Buffer.from(
            mergeAppServiceProvider(sourceContents.toString('utf8'), existingContents.toString('utf8')),
            'utf8'
        );
    }

    if (existingContents && Buffer.compare(sourceContents, existingContents) === 0) {
        return 'unchanged';
    }

    await fs.mkdir(path.dirname(destinationPath), { recursive: true });
    await fs.writeFile(destinationPath, sourceContents);

    return existingContents ? 'updated' : 'added';
}

export async function syncSkeleton({ sourceDir, destinationDir = path.join(kitsDir, 'Shared', 'Blank') }) {
    const summary = {
        added: [],
        updated: [],
        deleted: [],
        unchanged: [],
        skipped: [],
    };

    await fs.mkdir(destinationDir, { recursive: true });

    for (const relativePath of await collectAllowedFiles(sourceDir)) {
        const status = await syncAllowedFile({ sourceDir, destinationDir, relativePath });

        summary[status].push(relativePath);
    }

    return summary;
}

async function localDirectoryExists(source) {
    try {
        return (await fs.stat(source)).isDirectory();
    } catch (error) {
        if (error.code === 'ENOENT') {
            return false;
        }

        throw error;
    }
}

async function resolveDefaultBranch(repository) {
    const { stdout } = await runQuiet('git', ['ls-remote', '--symref', repository, 'HEAD']);
    const match = stdout.match(/^ref: refs\/heads\/(.+)\s+HEAD$/m);

    if (!match) {
        throw new Error(`Unable to resolve default branch for ${repository}`);
    }

    return match[1];
}

async function checkoutRemoteSource(repository, ref) {
    const tempDir = await fs.mkdtemp(path.join(os.tmpdir(), 'laravel-skeleton-upstream-'));
    const checkoutDir = path.join(tempDir, 'laravel');
    const resolvedRef = ref || await resolveDefaultBranch(repository);

    await runQuiet('git', ['clone', '--depth', '1', '--branch', resolvedRef, repository, checkoutDir]);

    return {
        sourceDir: checkoutDir,
        cleanup: () => fs.rm(tempDir, { recursive: true, force: true }),
        ref: resolvedRef,
    };
}

async function resolveSource() {
    const source = process.env.LARAVEL_SKELETON_SOURCE || upstreamRepository;
    const ref = process.env.LARAVEL_SKELETON_REF || null;
    const localSource = path.resolve(source);

    if (await localDirectoryExists(localSource)) {
        return {
            sourceDir: localSource,
            cleanup: async () => {},
            ref: ref || 'local',
        };
    }

    return checkoutRemoteSource(source, ref);
}

function printSummary(summary, sourceRef) {
    log(`Laravel skeleton source: ${sourceRef}`, 'blue');
    log(`Added: ${summary.added.length}`, 'green');
    log(`Updated: ${summary.updated.length}`, 'yellow');
    log(`Deleted: ${summary.deleted.length}`, 'yellow');
    log(`Unchanged: ${summary.unchanged.length}`, 'dim');
    log(`Skipped: ${summary.skipped.length}`, 'dim');
}

async function main() {
    const source = await resolveSource();

    try {
        const summary = await syncSkeleton({ sourceDir: source.sourceDir });

        printSummary(summary, source.ref);
    } finally {
        await source.cleanup();
    }
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
    main().catch(error => {
        log(`Laravel skeleton sync failed: ${error.message}`, 'red');

        if (error.output) {
            log(error.output, 'dim');
        }

        process.exit(1);
    });
}
