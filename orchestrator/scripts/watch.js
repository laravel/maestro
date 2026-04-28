#!/usr/bin/env node

import chokidar from 'chokidar';
import fs from 'fs';
import ignore from 'ignore';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const kitsDir = path.join(rootDir, 'kits');
const buildDir = path.join(rootDir, 'build');
const starterKitFile = path.join(orchestratorDir, 'storage', 'app', 'private', 'starter_kit');
const uiComponentsFile = path.join(__dirname, 'ui-components.json');
const manifestFile = path.join(__dirname, 'kit-manifest.json');
const args = process.argv.slice(2);
const initialSyncOnly = args.includes('--initial-sync-only');

const colors = {
    reset: '\x1b[0m',
    blue: '\x1b[34m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    dim: '\x1b[2m',
};

function log(message, color = 'reset') {
    const timestamp = new Date().toLocaleTimeString();
    console.log(`${colors.dim}[${timestamp}]${colors.reset} ${colors[color]}${message}${colors.reset}`);
}

/**
 * Load the shared kit manifest.
 */
function loadManifest() {
    return JSON.parse(fs.readFileSync(manifestFile, 'utf-8'));
}

/**
 * Load UI components mapping from the JSON configuration file.
 */
function loadUiComponents() {
    const content = fs.readFileSync(uiComponentsFile, 'utf-8');

    return JSON.parse(content);
}

const allowedEmptyTextFiles = new Set([
    'resources/js/app.js',
]);

/**
 * Get the kit type (react or vue) from the starter kit string.
 * Returns null for livewire kits since they don't use placeholders.
 */
function getKitType(starterKit) {
    if (starterKit.startsWith('react')) {
        return 'react';
    }
    if (starterKit.startsWith('svelte')) {
        return 'svelte';
    }
    if (starterKit.startsWith('vue')) {
        return 'vue';
    }

    return null;
}

function shouldRestorePlaceholders(relativePath, placeholderPaths) {
    return placeholderPaths.some(p => relativePath.startsWith(p));
}

/**
 * Restore placeholders in file content.
 * This reverses the replacePlaceholders logic from BuildCommand.php.
 * Only replaces values in specific contexts:
 * - Inertia::render('value'
 * - ->component('value')
 * - Route::inertia('path', 'value')
 */
function restorePlaceholders(content, kitType, uiComponents) {
    if (!kitType) {
        return content;
    }

    let modified = content;

    for (const [key, values] of Object.entries(uiComponents)) {
        const replacement = values[kitType];
        if (!replacement) {
            continue;
        }

        const placeholder = `{{${key}}}`;

        // Replace Inertia::render('value' with Inertia::render('{{placeholder}}'
        modified = modified.replace(
            new RegExp(`(Inertia::render\\(')${escapeRegExp(replacement)}(')`, 'g'),
            `$1${placeholder}$2`
        );

        // Replace ->component('value') with ->component('{{placeholder}}')
        modified = modified.replace(
            new RegExp(`(->component\\(')${escapeRegExp(replacement)}('\\))`, 'g'),
            `$1${placeholder}$2`
        );

        // Replace Route::inertia('path', 'value') with Route::inertia('path', '{{placeholder}}')
        modified = modified.replace(
            new RegExp(`(Route::inertia\\(\\s*'[^']*'\\s*,\\s*')${escapeRegExp(replacement)}(')`, 'g'),
            `$1${placeholder}$2`
        );
    }

    return modified;
}

/**
 * Escape special regex characters in a string.
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Restore variant placeholder in composer.json name field.
 * This reverses the variant replacement for Inertia kits.
 */
function restoreComposerVariant(content, kitType) {
    if (!kitType) {
        return content;
    }

    // Replace the variant (react/vue) back with {{variant}} in the name field
    // Handles patterns like "laravel/react-starter-kit" or "laravel/blank-react-starter-kit"
    return content.replace(
        new RegExp(`"name":\\s*"(laravel/(?:blank-)?)${kitType}(-starter-kit)"`, 'g'),
        '"name": "$1{{variant}}$2"'
    );
}

/**
 * Recursively get all files in a directory.
 */
function getAllFiles(dir, files = []) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (!entry.isDirectory()) {
            files.push(fullPath);

            continue;
        }

        // Skip common directories that shouldn't be synced
        if (entry.name === 'node_modules' || entry.name === 'vendor' || entry.name.startsWith('.')) {
            continue;
        }
        getAllFiles(fullPath, files);
    }

    return files;
}

/**
 * Check if file content differs from the destination.
 * For text files, compares processed content. For binary files, compares raw content.
 */
function fileNeedsSync(srcPath, destPath, processedContent) {
    if (!fs.existsSync(destPath)) {
        return true;
    }

    if (processedContent !== null) {
        // Text file: compare processed content
        const destContent = fs.readFileSync(destPath, 'utf-8');

        return processedContent !== destContent;
    }

    // Binary file: compare raw content
    const stat1 = fs.statSync(srcPath);
    const stat2 = fs.statSync(destPath);

    if (stat1.size !== stat2.size) {
        return true;
    }

    const content1 = fs.readFileSync(srcPath);
    const content2 = fs.readFileSync(destPath);

    return !content1.equals(content2);
}

/**
 * Collect all file paths across the active layer set for the current starter kit.
 * Returns a Set of relative paths (posix-style) that should exist in kits/.
 */
function collectKitFiles(folders) {
    const files = new Set();

    for (const folder of folders) {
        const folderPath = path.join(kitsDir, folder);

        if (!fs.existsSync(folderPath)) {
            continue;
        }

        for (const file of getAllFiles(folderPath)) {
            files.add(path.relative(folderPath, file).replace(/\\/g, '/'));
        }
    }

    return files;
}

/**
 * Perform initial sync of all files from build to kits.
 * This catches any changes that occurred while the watcher wasn't running.
 * When reconcile is true, also removes stale files in kits/ that no longer
 * exist in the built tree.
 */
function performInitialSync(folders, ig, kitType, uiComponents, starterKit, manifest, reconcile = true) {
    log('Performing initial sync to catch any missed changes...', 'blue');

    const allFiles = getAllFiles(buildDir);
    let syncedCount = 0;

    // Track which kit-relative paths exist in the build so we can reconcile.
    const buildRelPaths = new Set();

    for (const filePath of allFiles) {
        const relativePath = getRelativePath(filePath);

        // Skip files that match .gitignore patterns
        if (ig.ignores(relativePath)) {
            continue;
        }

        const destRelativePath = remapComponentsPath(relativePath, starterKit, manifest);
        buildRelPaths.add(destRelativePath);

        const targetFolder = getTargetFolder(destRelativePath, folders);
        const destPath = path.join(kitsDir, targetFolder, destRelativePath);
        const processedContent = processFileContent(filePath, relativePath, targetFolder, kitType, uiComponents, manifest);

        if (isBlockedEmptyTextSync(relativePath, processedContent, destPath)) {
            log(`Blocked empty file sync: ${relativePath} -> kits/${targetFolder}`, 'red');
            continue;
        }

        // Skip if file hasn't changed
        if (!fileNeedsSync(filePath, destPath, processedContent)) {
            continue;
        }

        try {
            writeToKit(filePath, destPath, processedContent);
            const syncLabel = destRelativePath !== relativePath
                ? `${relativePath} (remapped to ${destRelativePath})`
                : relativePath;
            log(`Synced: ${syncLabel} -> kits/${targetFolder}`, 'green');
            syncedCount++;
        } catch (error) {
            log(`Error syncing ${relativePath}: ${error.message}`, 'red');
        }
    }

    if (syncedCount > 0) {
        log(`Initial sync complete: ${syncedCount} file(s) updated`, 'green');
    } else {
        log('Initial sync complete: all files up to date', 'green');
    }

    // Reconcile stale files that exist in kits/ but not in the build.
    if (reconcile) {
        reconcileStaleFiles(folders, buildRelPaths, ig, starterKit, manifest);
    }
}

/**
 * Remove files from kits/ that no longer exist in the build output.
 * Only operates within the active layer set for the current starter kit.
 */
function reconcileStaleFiles(folders, buildRelPaths, ig, starterKit, manifest) {
    const kitFiles = collectKitFiles(folders);
    const excludedPaths = (manifest.kitExcludedFiles && manifest.kitExcludedFiles[starterKit]) || [];
    const isExcluded = (relPath) => excludedPaths.some(excluded => relPath === excluded || relPath.startsWith(excluded + '/'));
    let removedCount = 0;

    for (const relPath of kitFiles) {
        // If the file exists in the build output, keep it.
        if (buildRelPaths.has(relPath)) {
            continue;
        }

        if (isExcluded(relPath)) {
            continue;
        }

        // If the file would be ignored by .gitignore, skip it.
        if (ig.ignores(relPath)) {
            continue;
        }

        // Find which layer owns this file and remove it.
        for (let i = folders.length - 1; i >= 0; i--) {
            const candidate = path.join(kitsDir, folders[i], relPath);

            if (!fs.existsSync(candidate)) {
                continue;
            }

            try {
                fs.unlinkSync(candidate);
                log(`Reconciled (removed stale): kits/${folders[i]}/${relPath}`, 'yellow');
                removedCount++;

                // Clean up empty parent directories.
                removeEmptyParentDirs(candidate, path.join(kitsDir, folders[i]));
            } catch (error) {
                log(`Error removing stale file ${relPath}: ${error.message}`, 'red');
            }
        }
    }

    if (removedCount > 0) {
        log(`Reconciliation complete: ${removedCount} stale file(s) removed`, 'yellow');
    } else {
        log('Reconciliation complete: no stale files found', 'green');
    }
}

/**
 * Remove empty parent directories up to the kit directory root.
 */
function removeEmptyParentDirs(filePath, stopAt) {
    let dir = path.dirname(filePath);

    while (dir !== stopAt && dir.startsWith(stopAt)) {
        try {
            const entries = fs.readdirSync(dir);
            if (entries.length === 0) {
                fs.rmdirSync(dir);
            } else {
                break;
            }
        } catch {
            break;
        }
        dir = path.dirname(dir);
    }
}

/**
 * Recursively find all .gitignore files in a directory.
 */
function findGitignoreFiles(dir, files = []) {
    const gitignorePath = path.join(dir, '.gitignore');
    if (fs.existsSync(gitignorePath)) {
        files.push(gitignorePath);
    }

    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
        if (entry.isDirectory() && entry.name !== 'node_modules' && entry.name !== 'vendor' && !entry.name.startsWith('.')) {
            findGitignoreFiles(path.join(dir, entry.name), files);
        }
    }

    return files;
}

/**
 * Load and parse all .gitignore files from the build directory.
 */
function loadGitignores() {
    const ig = ignore();

    // Always ignore these files
    ig.add([
        '.git',
        'composer.lock',
        'package-lock.json',
        'yarn.lock',
        'pnpm-lock.yaml',
        'bun.lockb',
    ]);

    const gitignoreFiles = findGitignoreFiles(buildDir);

    for (const gitignorePath of gitignoreFiles) {
        const content = fs.readFileSync(gitignorePath, 'utf-8');
        const relativeDirPath = path.relative(buildDir, path.dirname(gitignorePath));

        // Parse each line and prefix with the relative directory path
        const lines = content.split('\n').map(line => {
            line = line.trim();

            // Skip empty lines and comments
            if (!line || line.startsWith('#')) {
                return null;
            }

            // If we're in a subdirectory, prefix the pattern
            if (relativeDirPath) {
                // Handle negation patterns
                if (line.startsWith('!')) {
                    return '!' + path.join(relativeDirPath, line.slice(1));
                }

                return path.join(relativeDirPath, line);
            }

            return line;
        }).filter(Boolean);

        if (lines.length > 0) {
            ig.add(lines);
            log(`Loaded .gitignore from ${relativeDirPath || 'root'}`, 'dim');
        }
    }

    return ig;
}

/**
 * Read the current starter kit from the storage file.
 */
function getStarterKit() {
    return !fs.existsSync(starterKitFile)
        ? null
        : fs.readFileSync(starterKitFile, 'utf-8').trim();
}

/**
 * Get the relative path from the build directory.
 */
function getRelativePath(filePath) {
    return path.relative(buildDir, filePath);
}

/**
 * Find the highest-priority kit folder that contains the file.
 * Returns the folder name or null if not found in any folder.
 */
function findSourceKitFolder(relativePath, folders) {
    for (let i = folders.length - 1; i >= 0; i--) {
        const kitPath = path.join(kitsDir, folders[i], relativePath);
        if (fs.existsSync(kitPath)) {
            return folders[i];
        }
    }

    return null;
}

function isInertiaKit(targetFolder) {
    return targetFolder.startsWith('Inertia/');
}

/**
 * Check if a file is a text file based on its extension.
 */
function isTextFile(relativePath) {
    return !relativePath.match(/\.(png|jpg|jpeg|gif|ico|woff|woff2|ttf|eot|svg)$/i);
}

function normalizePath(relativePath) {
    return relativePath.replace(/\\/g, '/');
}

function isAllowedEmptyTextFile(relativePath) {
    const normalizedPath = normalizePath(relativePath);
    const fileName = path.basename(normalizedPath);

    if (fileName === '.gitkeep') {
        return true;
    }

    return allowedEmptyTextFiles.has(normalizedPath);
}

function isBlockedEmptyTextSync(relativePath, processedContent, destPath) {
    if (processedContent === null || isAllowedEmptyTextFile(relativePath)) {
        return false;
    }

    if (processedContent.trim() !== '') {
        return false;
    }

    if (!fs.existsSync(destPath)) {
        return true;
    }

    return fs.statSync(destPath).size > 0;
}

/**
 * Remap relocated paths for the livewire-components variant.
 *
 * The build process (relocateAuthViewsForComponents in BuildCommand.php) moves:
 *   pages/settings/layout.blade.php  -> components/settings/layout.blade.php
 *   pages/auth/**                    -> livewire/auth/**
 *
 * When syncing back to kits/ we need to reverse this so the file lands in
 * its original source layer (Livewire/Base or Livewire/Fortify).
 *
 * Uses componentsRelocations from the shared manifest.
 */
function remapComponentsPath(relativePath, starterKit, manifest) {
    if (starterKit !== 'livewire-components') {
        return relativePath;
    }

    const normalized = normalizePath(relativePath);

    for (const rule of manifest.componentsRelocations) {
        const toNorm = normalizePath(rule.to);
        const fromNorm = normalizePath(rule.from);

        if (rule.directory) {
            // Directory prefix match: livewire/auth/** -> pages/auth/**
            if (normalized.startsWith(toNorm)) {
                return fromNorm + normalized.slice(toNorm.length);
            }
        } else {
            // Exact file match
            if (normalized === toNorm) {
                return fromNorm;
            }
        }
    }

    return relativePath;
}

/**
 * Get the target kit folder for a file.
 * Returns the highest-priority folder that contains the file, or the highest-priority folder if not found.
 */
function getTargetFolder(relativePath, folders) {
    return findSourceKitFolder(relativePath, folders) ?? folders[folders.length - 1];
}

/**
 * Process file content, applying placeholder restoration if needed.
 * Returns the processed content for text files, or null for binary files.
 */
function processFileContent(srcPath, relativePath, targetFolder, kitType, uiComponents, manifest) {
    if (!isTextFile(relativePath)) {
        return null;
    }

    let content = fs.readFileSync(srcPath, 'utf-8');

    // Apply placeholder restoration for specific paths
    if (kitType && shouldRestorePlaceholders(relativePath, manifest.placeholderSearchPaths)) {
        content = restorePlaceholders(content, kitType, uiComponents);
    }

    // Apply variant restoration for composer.json in Inertia kits
    if (kitType && relativePath === 'composer.json' && isInertiaKit(targetFolder)) {
        content = restoreComposerVariant(content, kitType);
    }

    return content;
}

/**
 * Write a file to the kit folder.
 * Handles both text files (with processed content) and binary files.
 */
function writeToKit(srcPath, destPath, processedContent) {
    const destDir = path.dirname(destPath);

    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }

    if (processedContent !== null) {
        fs.writeFileSync(destPath, processedContent);

        return;
    }

    fs.copyFileSync(srcPath, destPath);
}

/**
 * Copy a file from build to the appropriate kit folder.
 * Restores placeholders for files in placeholder paths.
 */
function copyToKit(srcPath, relativePath, folders, kitType, uiComponents, starterKit, manifest) {
    const destRelativePath = remapComponentsPath(relativePath, starterKit, manifest);
    const targetFolder = getTargetFolder(destRelativePath, folders);
    const destPath = path.join(kitsDir, targetFolder, destRelativePath);

    try {
        const processedContent = processFileContent(srcPath, relativePath, targetFolder, kitType, uiComponents, manifest);

        if (isBlockedEmptyTextSync(relativePath, processedContent, destPath)) {
            log(`Blocked empty file sync: ${relativePath} -> kits/${targetFolder}`, 'red');

            return;
        }

        writeToKit(srcPath, destPath, processedContent);
        const copyLabel = destRelativePath !== relativePath
            ? `${relativePath} (remapped to ${destRelativePath})`
            : relativePath;
        log(`Copied: ${copyLabel} -> kits/${targetFolder}`, 'green');
    } catch (error) {
        log(`Error copying ${relativePath}: ${error.message}`, 'red');
    }
}

/**
 * Delete a file from the appropriate kit folder.
 */
function deleteFromKit(relativePath, folders, starterKit, manifest) {
    const destRelativePath = remapComponentsPath(relativePath, starterKit, manifest);

    // Find the highest-priority folder that has this file
    const targetFolder = findSourceKitFolder(destRelativePath, folders);

    if (!targetFolder) {
        return;
    }

    const targetPath = path.join(kitsDir, targetFolder, destRelativePath);

    try {
        if (fs.existsSync(targetPath)) {
            fs.unlinkSync(targetPath);
            log(`Deleted: kits/${targetFolder}/${destRelativePath}`, 'yellow');
            removeEmptyParentDirs(targetPath, path.join(kitsDir, targetFolder));
        }
    } catch (error) {
        log(`Error deleting ${destRelativePath}: ${error.message}`, 'red');
    }
}

/**
 * Delete a directory and its contents from the appropriate kit folder(s).
 */
function deleteDirFromKit(relativePath, folders, starterKit, manifest) {
    const destRelativePath = remapComponentsPath(relativePath, starterKit, manifest);

    for (let i = folders.length - 1; i >= 0; i--) {
        const targetDir = path.join(kitsDir, folders[i], destRelativePath);

        if (!fs.existsSync(targetDir)) {
            continue;
        }

        try {
            fs.rmSync(targetDir, { recursive: true, force: true });
            log(`Deleted directory: kits/${folders[i]}/${destRelativePath}`, 'yellow');

            removeEmptyParentDirs(targetDir, path.join(kitsDir, folders[i]));
        } catch (error) {
            log(`Error deleting directory ${destRelativePath}: ${error.message}`, 'red');
        }
    }
}

/**
 * Handle file change events from the build directory.
 */
function handleFileChange(eventType, filePath, folders, ig, kitType, uiComponents, starterKit, manifest) {
    const relativePath = getRelativePath(filePath);

    // Skip files that match .gitignore patterns
    if (ig.ignores(relativePath)) {
        return;
    }

    if (eventType === 'unlink') {
        deleteFromKit(relativePath, folders, starterKit, manifest);

        return;
    }

    if (eventType === 'unlinkDir') {
        deleteDirFromKit(relativePath, folders, starterKit, manifest);

        return;
    }

    copyToKit(filePath, relativePath, folders, kitType, uiComponents, starterKit, manifest);
}

function startWatching() {
    const starterKit = getStarterKit();

    if (!starterKit) {
        log('No starter kit found. Please run "php artisan build" first.', 'red');
        process.exit(1);
    }

    const manifest = loadManifest();
    const folders = manifest.kitFolderMap[starterKit];

    if (!folders) {
        log(`Unknown starter kit: ${starterKit}`, 'red');
        process.exit(1);
    }

    if (!fs.existsSync(buildDir)) {
        log('Build directory does not exist. Please run "php artisan build" first.', 'red');
        process.exit(1);
    }

    const kitType = getKitType(starterKit);
    const uiComponents = kitType ? loadUiComponents() : null;

    log(`Watching build directory for ${starterKit} kit`, 'blue');
    log(`Changes will be copied to:`, 'blue');
    folders.forEach(folder => log(`  - kits/${folder}`, 'blue'));

    if (kitType) {
        log(`Placeholder restoration enabled for ${kitType} kit`, 'blue');
    }

    const ig = loadGitignores();

    // Perform initial sync to catch any changes that occurred while watcher wasn't running
    performInitialSync(folders, ig, kitType, uiComponents, starterKit, manifest);

    if (initialSyncOnly) {
        log('Initial sync only mode enabled. Exiting without starting watcher.', 'blue');

        return;
    }

    const watcher = chokidar.watch(buildDir, {
        ignored: /(^|[\/\\])\../, // ignore dotfiles
        persistent: true,
        ignoreInitial: true,
    });

    watcher
        .on('add', filePath => handleFileChange('add', filePath, folders, ig, kitType, uiComponents, starterKit, manifest))
        .on('change', filePath => handleFileChange('change', filePath, folders, ig, kitType, uiComponents, starterKit, manifest))
        .on('unlink', filePath => handleFileChange('unlink', filePath, folders, ig, kitType, uiComponents, starterKit, manifest))
        .on('unlinkDir', filePath => handleFileChange('unlinkDir', filePath, folders, ig, kitType, uiComponents, starterKit, manifest))
        .on('ready', () => {
            log('Watcher ready. Waiting for changes in build directory...', 'green');
        })
        .on('error', error => {
            log(`Watcher error: ${error.message}`, 'red');
        });
}

startWatching();
