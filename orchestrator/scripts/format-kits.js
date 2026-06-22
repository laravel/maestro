#!/usr/bin/env node

import {
    buildDir,
    colors,
    log,
    orchestratorDir,
    removeBuildDirectory,
    runMatrix,
    runQuiet,
} from './kit-helpers.js';

const variants = [
    {
        key: 'react-blank',
        display: 'React Blank',
        framework: 'react',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--blank'],
    },
    {
        key: 'react',
        display: 'React Fortify',
        framework: 'react',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=React'],
    },
    {
        key: 'react-workos',
        display: 'React WorkOS',
        framework: 'react',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos'],
    },
    {
        key: 'react-teams',
        display: 'React Teams (Fortify)',
        framework: 'react',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--teams'],
    },
    {
        key: 'react-workos-teams',
        display: 'React Teams (WorkOS)',
        framework: 'react',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=React', '--workos', '--teams'],
    },
    {
        key: 'svelte-blank',
        display: 'Svelte Blank',
        framework: 'svelte',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--blank'],
    },
    {
        key: 'svelte',
        display: 'Svelte Fortify',
        framework: 'svelte',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte'],
    },
    {
        key: 'svelte-workos',
        display: 'Svelte WorkOS',
        framework: 'svelte',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos'],
    },
    {
        key: 'svelte-teams',
        display: 'Svelte Teams (Fortify)',
        framework: 'svelte',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--teams'],
    },
    {
        key: 'svelte-workos-teams',
        display: 'Svelte Teams (WorkOS)',
        framework: 'svelte',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Svelte', '--workos', '--teams'],
    },
    {
        key: 'vue-blank',
        display: 'Vue Blank',
        framework: 'vue',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--blank'],
    },
    {
        key: 'vue',
        display: 'Vue Fortify',
        framework: 'vue',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Vue'],
    },
    {
        key: 'vue-workos',
        display: 'Vue WorkOS',
        framework: 'vue',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos'],
    },
    {
        key: 'vue-teams',
        display: 'Vue Teams (Fortify)',
        framework: 'vue',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--teams'],
    },
    {
        key: 'vue-workos-teams',
        display: 'Vue Teams (WorkOS)',
        framework: 'vue',
        variant: 'teams',
        buildArgs: ['build', '--no-interaction', '--kit=Vue', '--workos', '--teams'],
    },
];

async function formatVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display}`, 'blue');

    removeBuildDirectory();

    log('  Building variant...', 'dim');
    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    log('  Installing npm deps...', 'dim');
    await runQuiet('npm', ['install'], { cwd: buildDir });

    log('  Running format...', 'dim');
    await runQuiet('npm', ['run', 'format'], { cwd: buildDir });

    log('  Syncing changes back to kits...', 'dim');
    await runQuiet('node', ['scripts/watch.js', '--initial-sync-only'], { cwd: orchestratorDir });
}

await runMatrix({
    scriptLabel: 'kits:format',
    allVariants: variants,
    runVariant: formatVariant,
    successVerb: 'Formatted',
    guardActiveWatcher: true,
});
