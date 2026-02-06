<script lang="ts">
    import type { Snippet } from 'svelte';
    import { setContext } from 'svelte';
    import { DROPDOWN_MENU_CONTEXT, type DropdownMenuContext } from './context';

    let { open = $bindable(false), children }: { open?: boolean; children?: Snippet } = $props();

    const context: DropdownMenuContext = {
        open: () => open,
        setOpen: (value: boolean) => {
            open = value;
        },
    };

    setContext(DROPDOWN_MENU_CONTEXT, context);
</script>

<div class="relative inline-flex">
    {@render children?.()}
</div>
