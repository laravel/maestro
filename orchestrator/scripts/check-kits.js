#!/usr/bin/env node

import { buildDir, log, orchestratorDir, removeBuildDirectory, runMatrix, runQuiet } from './kit-helpers.js';

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
        key: 'livewire-blank',
        display: 'Livewire Blank',
        framework: 'livewire',
        variant: 'blank',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--blank'],
    },
    {
        key: 'livewire',
        display: 'Livewire Fortify',
        framework: 'livewire',
        variant: 'fortify',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire'],
    },
    {
        key: 'livewire-components',
        display: 'Livewire Components',
        framework: 'livewire',
        variant: 'components',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--components'],
    },
    {
        key: 'livewire-workos',
        display: 'Livewire WorkOS',
        framework: 'livewire',
        variant: 'workos',
        buildArgs: ['build', '--no-interaction', '--kit=Livewire', '--workos'],
    },
];

async function checkCurrentBuild() {
    log('  Installing dependencies...', 'dim');
    await runQuiet('composer', ['setup'], { cwd: buildDir });

    log('  Running ci:check...', 'dim');
    await runQuiet('composer', ['ci:check'], { cwd: buildDir });
}

async function checkVariant(variant, index, total) {
    log(`\n[${index}/${total}] ${variant.display}`, 'blue');

    removeBuildDirectory();

    log('  Building variant...', 'dim');
    await runQuiet('php', ['artisan', ...variant.buildArgs], { cwd: orchestratorDir });

    await checkCurrentBuild();
}

runMatrix({
    scriptLabel: 'kits:check',
    allVariants: variants,
    runVariant: checkVariant,
}).catch(error => {
    log(`\nCheck kits failed: ${error.message}`, 'red');
    process.exit(1);
});
