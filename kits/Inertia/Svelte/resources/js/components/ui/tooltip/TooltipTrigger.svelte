<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { TOOLTIP_CONTEXT, type TooltipContext } from './context';

    let { asChild = false, children }: { asChild?: boolean; children?: Snippet<[Record<string, unknown>]> } = $props();

    const { setOpen } = getContext<TooltipContext>(TOOLTIP_CONTEXT);

    const handleEnter = () => setOpen(true);
    const handleLeave = () => setOpen(false);
</script>

{#if asChild}
    {@render children?.({ onMouseenter: handleEnter, onMouseleave: handleLeave })}
{:else}
    <span on:mouseenter={handleEnter} on:mouseleave={handleLeave}>
        <slot />
    </span>
{/if}
