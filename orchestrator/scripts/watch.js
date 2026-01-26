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
 * Kit folder mapping based on starter kit value.
 * Folders are listed in priority order (lowest to highest).
 * Higher priority folders override lower priority ones.
 * Shared folders contain files common to both Livewire and Inertia kits.
 */
const kitFolderMap = {
    // Livewire variants
    'livewire-blank': ['Shared/Blank', 'Livewire/Blank'],
    'livewire': ['Shared/Blank', 'Livewire/Blank', 'Shared/Base', 'Livewire/Base', 'Shared/Fortify', 'Livewire/Fortify'],
    'livewire-components': ['Shared/Blank', 'Livewire/Blank', 'Shared/Base', 'Livewire/Base', 'Shared/Fortify', 'Livewire/Fortify', 'Livewire/Components'],
    'livewire-workos': ['Shared/Blank', 'Livewire/Blank', 'Shared/Base', 'Livewire/Base', 'Shared/WorkOS', 'Livewire/WorkOS'],

    // React variants
    'react-blank': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/React'],
    'react': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/React', 'Shared/Base', 'Inertia/Base', 'Inertia/React', 'Shared/Fortify', 'Inertia/Fortify/Base', 'Inertia/Fortify/React'],
    'react-workos': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/React', 'Shared/Base', 'Inertia/Base', 'Inertia/React', 'Shared/WorkOS', 'Inertia/WorkOS/Base', 'Inertia/WorkOS/React'],

    // Vue variants
    'vue-blank': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/Vue'],
    'vue': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/Vue', 'Shared/Base', 'Inertia/Base', 'Inertia/Vue', 'Shared/Fortify', 'Inertia/Fortify/Base', 'Inertia/Fortify/Vue'],
    'vue-workos': ['Shared/Blank', 'Inertia/Blank/Base', 'Inertia/Blank/Vue', 'Shared/Base', 'Inertia/Base', 'Inertia/Vue', 'Shared/WorkOS', 'Inertia/WorkOS/Base', 'Inertia/WorkOS/Vue'],
};

/**
 * Load UI components mapping from the JSON configuration file.
 */
function loadUiComponents() {
    const content = fs.readFileSync(uiComponentsFile, 'utf-8');
    return JSON.parse(content);
}

/**
 * Paths (relative to build) where placeholders should be restored.
 * These match the searchPaths in BuildCommand.php replacePlaceholders method.
 */
const placeholderPaths = [
    'app/Http/Controllers',
    'app/Providers',
    'routes',
    'tests',
];

/**
 * Get the kit type (react or vue) from the starter kit string.
 * Returns null for livewire kits since they don't use placeholders.
 */
function getKitType(starterKit) {
    if (starterKit.startsWith('react')) {
        return 'react';
    }
    if (starterKit.startsWith('vue')) {
        return 'vue';
    }
    return null;
}

function shouldRestorePlaceholders(relativePath) {
    return placeholderPaths.some(p => relativePath.startsWith(p));
}

/**
 * Restore placeholders in file content.
 * This reverses the replacePlaceholders logic from BuildCommand.php.
 * Only replaces values in specific contexts:
 * - Inertia::render('value'
 * - ->component('value')
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
    if (!fs.existsSync(starterKitFile)) {
        return null;
    }
    return fs.readFileSync(starterKitFile, 'utf-8').trim();
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
 * Copy a file from build to the appropriate kit folder.
 * Restores placeholders for files in placeholder paths.
 */
function copyToKit(srcPath, relativePath, folders, kitType, uiComponents) {
    // Find the highest-priority folder that has this file
    let targetFolder = findSourceKitFolder(relativePath, folders);

    // If no folder has this file, use the highest-priority folder
    if (!targetFolder) {
        targetFolder = folders[folders.length - 1];
    }

    const destPath = path.join(kitsDir, targetFolder, relativePath);
    const destDir = path.dirname(destPath);

    try {
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }

        // Check if we need to restore placeholders for this file
        if (kitType && shouldRestorePlaceholders(relativePath)) {
            const content = fs.readFileSync(srcPath, 'utf-8');
            const restoredContent = restorePlaceholders(content, kitType, uiComponents);

            if (content !== restoredContent) {
                fs.writeFileSync(destPath, restoredContent);
                log(`Copied (placeholders restored): ${relativePath} -> kits/${targetFolder}`, 'green');
                return;
            }
        }

        // Check if we need to restore variant placeholder in composer.json for Inertia kits
        if (kitType && relativePath === 'composer.json' && isInertiaKit(targetFolder)) {
            const content = fs.readFileSync(srcPath, 'utf-8');
            const restoredContent = restoreComposerVariant(content, kitType);

            if (content !== restoredContent) {
                fs.writeFileSync(destPath, restoredContent);
                log(`Copied (variant restored): ${relativePath} -> kits/${targetFolder}`, 'green');
                return;
            }
        }

        fs.copyFileSync(srcPath, destPath);
        log(`Copied: ${relativePath} -> kits/${targetFolder}`, 'green');
    } catch (error) {
        log(`Error copying ${relativePath}: ${error.message}`, 'red');
    }
}

/**
 * Delete a file from the appropriate kit folder.
 */
function deleteFromKit(relativePath, folders) {
    // Find the highest-priority folder that has this file
    const targetFolder = findSourceKitFolder(relativePath, folders);

    if (!targetFolder) {
        return;
    }

    const targetPath = path.join(kitsDir, targetFolder, relativePath);

    try {
        if (fs.existsSync(targetPath)) {
            fs.unlinkSync(targetPath);
            log(`Deleted: kits/${targetFolder}/${relativePath}`, 'yellow');
        }
    } catch (error) {
        log(`Error deleting ${relativePath}: ${error.message}`, 'red');
    }
}

/**
 * Handle file change events from the build directory.
 */
function handleFileChange(eventType, filePath, folders, ig, kitType, uiComponents) {
    const relativePath = getRelativePath(filePath);

    // Skip files that match .gitignore patterns
    if (ig.ignores(relativePath)) {
        return;
    }

    if (eventType === 'unlink') {
        deleteFromKit(relativePath, folders);
        return;
    }

    copyToKit(filePath, relativePath, folders, kitType, uiComponents);
}

function startWatching() {
    const starterKit = getStarterKit();

    if (!starterKit) {
        log('No starter kit found. Please run "php artisan build" first.', 'red');
        process.exit(1);
    }

    const folders = kitFolderMap[starterKit];

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

    const watcher = chokidar.watch(buildDir, {
        ignored: /(^|[\/\\])\../, // ignore dotfiles
        persistent: true,
        ignoreInitial: true,
    });

    watcher
        .on('add', filePath => handleFileChange('add', filePath, folders, ig, kitType, uiComponents))
        .on('change', filePath => handleFileChange('change', filePath, folders, ig, kitType, uiComponents))
        .on('unlink', filePath => handleFileChange('unlink', filePath, folders, ig, kitType, uiComponents))
        .on('ready', () => {
            log('Watcher ready. Waiting for changes in build directory...', 'green');
        })
        .on('error', error => {
            log(`Watcher error: ${error.message}`, 'red');
        });
}

startWatching();
