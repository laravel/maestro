<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
    import { SIDEBAR_CONTEXT, type SidebarContext } from './context';

    type Size = 'default' | 'lg';

    let {
        asChild = false,
        class: className = '',
        isActive = false,
        size = 'default',
        tooltip,
        children,
        ...rest
    }: {
        asChild?: boolean;
        class?: string;
        isActive?: boolean;
        size?: Size;
        tooltip?: string;
        children?: Snippet<[Record<string, unknown>]>;
        [key: string]: unknown;
    } = $props();

    const { isMobile, state } = getContext<SidebarContext>(SIDEBAR_CONTEXT);

    const base =
        'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground';
    const sizeClasses: Record<Size, string> = {
        default: 'h-9',
        lg: 'h-12 px-3',
    };

    const activeClasses = isActive ? 'bg-accent text-accent-foreground' : '';
    const classes = cn(base, sizeClasses[size], activeClasses, className);
</script>

{#if tooltip}
    <Tooltip>
        <TooltipTrigger asChild>
            {#if asChild}
                {@render children?.({ class: classes, ...rest })}
            {:else}
                <button class={classes} type="button" {...rest}>
                    <slot />
                </button>
            {/if}
        </TooltipTrigger>
        <TooltipContent side="right" align="center" hidden={$state !== 'collapsed' || $isMobile}>
            {tooltip}
        </TooltipContent>
    </Tooltip>
{:else}
    {#if asChild}
        {@render children?.({ class: classes, ...rest })}
    {:else}
        <button class={classes} type="button" {...rest}>
            <slot />
        </button>
    {/if}
{/if}
