<script lang="ts">
    import type { Snippet } from 'svelte';
    import { SidebarProvider } from '@/components/ui/sidebar';
    import type { AppShellVariant } from '@/types';
    import { page } from '@inertiajs/svelte';

    let {
        variant = 'sidebar',
        class: className = '',
        children,
    }: {
        variant?: AppShellVariant;
        class?: string;
        children?: Snippet;
    } = $props();

    const isOpen = $derived($page.props.sidebarOpen);
</script>

{#if variant === 'header'}
    <div class="flex min-h-screen w-full flex-col {className}">
        {@render children?.()}
    </div>
{:else}
    <SidebarProvider defaultOpen={isOpen}>
        {@render children?.()}
    </SidebarProvider>
{/if}
