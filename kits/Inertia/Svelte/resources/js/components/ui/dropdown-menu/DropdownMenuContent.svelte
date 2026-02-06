<script lang="ts">
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { DROPDOWN_MENU_CONTEXT, type DropdownMenuContext } from './context';

    let { align = 'start', side = 'bottom', class: className = '' } = $props();

    const { open, setOpen } = getContext<DropdownMenuContext>(DROPDOWN_MENU_CONTEXT);

    const alignClasses: Record<string, string> = {
        start: 'left-0',
        center: 'left-1/2 -translate-x-1/2',
        end: 'right-0',
    };

    const sideClasses: Record<string, string> = {
        bottom: 'top-full mt-2',
        top: 'bottom-full mb-2',
        left: 'right-full mr-2',
        right: 'left-full ml-2',
    };

    const close = () => setOpen(false);
</script>

{#if open()}
    <div
        class={cn(
            'absolute z-50 min-w-48 rounded-md border bg-popover p-2 text-popover-foreground shadow-md',
            alignClasses[align] ?? alignClasses.start,
            sideClasses[side] ?? sideClasses.bottom,
            className,
        )}
        role="menu"
        on:keydown={(event) => event.key === 'Escape' && close()}
    >
        <slot />
    </div>
{/if}
