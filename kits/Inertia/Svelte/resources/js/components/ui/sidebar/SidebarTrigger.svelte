<script lang="ts">
    import { getContext } from 'svelte';
    import { PanelLeftClose, PanelLeftOpen } from 'lucide-svelte';
    import { Button } from '@/components/ui/button';
    import { cn } from '@/lib/utils';
    import { SIDEBAR_CONTEXT, type SidebarContext } from './context';

    let { class: className = '' } = $props();

    const { isMobile, state, toggleSidebar } = getContext<SidebarContext>(SIDEBAR_CONTEXT);
</script>

<Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    class={cn('h-7 w-7', className)}
    onClick={toggleSidebar}
>
    {#if $isMobile || $state === 'collapsed'}
        <PanelLeftOpen />
    {:else}
        <PanelLeftClose />
    {/if}
    <span class="sr-only">Toggle Sidebar</span>
</Button>
