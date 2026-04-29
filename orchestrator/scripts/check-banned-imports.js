#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const orchestratorDir = path.dirname(__dirname);
const rootDir = path.dirname(orchestratorDir);
const targetDir = path.join(rootDir, 'kits', 'Inertia', 'Fortify');

const colors = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    yellow: '\x1b[33m',
    dim: '\x1b[2m',
};

/**
 * Each rule fails the build if ANY of its `patterns` matches a file.
 * Patterns are RegExps tested against the file's full source.
 */
const rules = [
    {
        label: "static import from '@/routes/register'",
        patterns: [/from\s+['"]@\/routes\/register['"]/],
    },
    {
        label: "named import { register } from '@/routes'",
        patterns: [/import\s*{[^}]*\bregister\b[^}]*}\s*from\s+['"]@\/routes['"]/],
    },
    {
        label: "static import from '@/routes/verification'",
        patterns: [/from\s+['"]@\/routes\/verification['"]/],
    },
    {
        label: "static import from '@/routes/two-factor' or '@/routes/two-factor/login'",
        patterns: [/from\s+['"]@\/routes\/two-factor(\/login)?['"]/],
    },
    {
        label: "named imports { request | email | update } from '@/routes/password'",
        patterns: [
            /import\s*{[^}]*\b(request|email|update)\b[^}]*}\s*from\s+['"]@\/routes\/password['"]/,
        ],
    },
];

const allowedExtensions = ['.ts', '.tsx', '.vue', '.svelte'];

function walk(dir, files = []) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            if (entry.name === 'node_modules') {
                continue;
            }

            walk(full, files);

            continue;
        }

        if (allowedExtensions.includes(path.extname(entry.name))) {
            files.push(full);
        }
    }

    return files;
}

if (!fs.existsSync(targetDir)) {
    console.error(`${colors.red}Target directory not found: ${targetDir}${colors.reset}`);
    process.exit(1);
}

const files = walk(targetDir);
const violations = [];

for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');

    for (const rule of rules) {
        for (const pattern of rule.patterns) {
            if (pattern.test(source)) {
                violations.push({ file: path.relative(rootDir, file), rule: rule.label });
            }
        }
    }
}

if (violations.length > 0) {
    console.error(`${colors.red}Banned feature-gated imports found in kits/Inertia/Fortify:${colors.reset}`);
    console.error(`${colors.dim}These imports break the build when their Fortify feature is disabled.${colors.reset}\n`);

    for (const { file, rule } of violations) {
        console.error(`  ${colors.yellow}${rule}${colors.reset}`);
        console.error(`    ${colors.dim}${file}${colors.reset}`);
    }

    console.error(`\n${colors.red}${violations.length} violation(s).${colors.reset} Use the URL prop pattern instead — see App\\Support\\FortifyFeaturePayload.`);
    process.exit(1);
}

console.log('No banned feature-gated imports found in kits/Inertia/Fortify.');
