<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { SHEET_CONTEXT, type SheetContext } from './context';

    let { asChild = false, children }: { asChild?: boolean; children?: Snippet<[Record<string, unknown>]> } = $props();

    const { setOpen, open } = getContext<SheetContext>(SHEET_CONTEXT);

    const handleClick = () => setOpen(!open());
</script>

{#if asChild}
    {@render children?.({ onClick: handleClick, 'aria-expanded': open() })}
{:else}
    <button type="button" on:click={handleClick} aria-expanded={open()}>
        <slot />
    </button>
{/if}
