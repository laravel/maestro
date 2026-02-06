<script lang="ts">
    import type { Snippet } from 'svelte';
    import { setContext } from 'svelte';
    import { TOOLTIP_CONTEXT, type TooltipContext } from './context';

    let { open = false, children }: { open?: boolean; children?: Snippet } = $props();

    const context: TooltipContext = {
        open: () => open,
        setOpen: (value: boolean) => {
            open = value;
        },
    };

    setContext(TOOLTIP_CONTEXT, context);
</script>

<div class="relative inline-flex">
    {@render children?.()}
</div>
