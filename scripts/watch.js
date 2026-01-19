#!/usr/bin/env node

import chokidar from 'chokidar';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.dirname(__dirname);
const kitsDir = path.join(rootDir, 'kits');
const buildDir = path.join(rootDir, 'build');
const starterKitFile = path.join(rootDir, 'storage', 'app', 'private', 'starter_kit');

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
 * Watch folder mapping based on starter kit value.
 * Folders are listed in priority order (lowest to highest).
 * Higher priority folders override lower priority ones.
 */
const kitFolderMap = {
    'livewire': ['Livewire/Base'],
    'livewire-components': ['Livewire/Base', 'Livewire/Components'],
    'livewire-workos': ['Livewire/Base', 'Livewire/WorkOS'],
    'react': ['Inertia/Base', 'Inertia/React'],
    'react-workos': ['Inertia/Base', 'Inertia/React', 'Inertia/WorkOS/Base', 'Inertia/WorkOS/React'],
    'vue': ['Inertia/Base', 'Inertia/Vue'],
    'vue-workos': ['Inertia/Base', 'Inertia/Vue', 'Inertia/WorkOS/Base', 'Inertia/WorkOS/Vue'],
};

/**
 * Read the current starter kit from the storage file.
 */
function getStarterKit() {
    if (!fs.existsSync(starterKitFile)) {
        return null;
    }
    return fs.readFileSync(starterKitFile, 'utf-8').trim();
}

function getRelativePath(filePath, kitFolder) {
    const kitPath = path.join(kitsDir, kitFolder);
    return path.relative(kitPath, filePath);
}

/**
 * Check if a file exists in any higher-priority folder.
 */
function hasOverrideInHigherPriority(relativePath, currentFolderIndex, folders) {
    for (let i = currentFolderIndex + 1; i < folders.length; i++) {
        const overridePath = path.join(kitsDir, folders[i], relativePath);
        if (fs.existsSync(overridePath)) {
            return true;
        }
    }
    return false;
}

function copyFile(srcPath, relativePath) {
    const destPath = path.join(buildDir, relativePath);
    const destDir = path.dirname(destPath);

    try {
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }
        fs.copyFileSync(srcPath, destPath);
        log(`Copied: ${relativePath}`, 'green');
    } catch (error) {
        log(`Error copying ${relativePath}: ${error.message}`, 'red');
    }
}

function deleteFile(relativePath, folders) {
    const destPath = path.join(buildDir, relativePath);

    // Before deleting, check if any lower-priority folder has this file
    for (let i = folders.length - 1; i >= 0; i--) {
        const sourcePath = path.join(kitsDir, folders[i], relativePath);
        if (fs.existsSync(sourcePath)) {
            // Copy from the highest priority folder that has the file
            copyFile(sourcePath, relativePath);
            return;
        }
    }

    try {
        if (fs.existsSync(destPath)) {
            fs.unlinkSync(destPath);
            log(`Deleted: ${relativePath}`, 'yellow');
        }
    } catch (error) {
        log(`Error deleting ${relativePath}: ${error.message}`, 'red');
    }
}

function handleFileChange(eventType, filePath, folders) {
    // Find which kit folder this file belongs to
    let kitFolder = null;
    let folderIndex = -1;

    for (let i = 0; i < folders.length; i++) {
        const kitPath = path.join(kitsDir, folders[i]);
        if (filePath.startsWith(kitPath + path.sep)) {
            kitFolder = folders[i];
            folderIndex = i;
            break;
        }
    }

    if (!kitFolder) {
        return;
    }

    const relativePath = getRelativePath(filePath, kitFolder);

    // Skip hidden files and directories
    if (relativePath.split(path.sep).some(part => part.startsWith('.'))) {
        return;
    }

    if (eventType === 'unlink') {
        deleteFile(relativePath, folders);
        return;
    }

    // For add/change events, check if there's an override in higher-priority folders
    if (hasOverrideInHigherPriority(relativePath, folderIndex, folders)) {
        log(`Skipped: ${relativePath} (override exists in higher-priority folder)`, 'dim');
        return;
    }

    copyFile(filePath, relativePath);
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

    const watchPaths = folders.map(folder => path.join(kitsDir, folder));

    log(`Watching ${starterKit} kit folders:`, 'blue');
    folders.forEach(folder => log(`  - kits/${folder}`, 'blue'));

    const watcher = chokidar.watch(watchPaths, {
        ignored: /(^|[\/\\])\../, // ignore dotfiles
        persistent: true,
        ignoreInitial: true,
    });

    watcher
        .on('add', filePath => handleFileChange('add', filePath, folders))
        .on('change', filePath => handleFileChange('change', filePath, folders))
        .on('unlink', filePath => handleFileChange('unlink', filePath, folders))
        .on('ready', () => {
            log('Watcher ready. Waiting for changes...', 'green');
        })
        .on('error', error => {
            log(`Watcher error: ${error.message}`, 'red');
        });
}

startWatching();
