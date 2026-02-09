<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { TOOLTIP_CONTEXT, type TooltipContext } from './context';

    let {
        side = 'top',
        align = 'center',
        hidden = false,
        class: className = '',
        children,
    }: {
        side?: 'top' | 'right' | 'bottom' | 'left';
        align?: 'start' | 'center' | 'end';
        hidden?: boolean;
        class?: string;
        children?: Snippet;
    } = $props();

    const { open } = getContext<TooltipContext>(TOOLTIP_CONTEXT);

    const sideClasses: Record<string, string> = {
        top: 'bottom-full mb-2',
        bottom: 'top-full mt-2',
        left: 'right-full mr-2',
        right: 'left-full ml-2',
    };

    const alignClasses: Record<string, string> = {
        start: 'left-0',
        center: 'left-1/2 -translate-x-1/2',
        end: 'right-0',
    };
</script>

{#if open() && !hidden}
    <div
        class={cn(
            'absolute z-50 rounded-md bg-popover px-2 py-1 text-xs text-popover-foreground shadow-md',
            sideClasses[side] ?? sideClasses.top,
            alignClasses[align] ?? alignClasses.center,
            className,
        )}
    >
        {@render children?.()}
    </div>
{/if}
