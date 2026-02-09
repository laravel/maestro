<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { SHEET_CONTEXT, type SheetContext } from './context';

    let {
        side = 'right',
        class: className = '',
        children,
    }: {
        side?: 'right' | 'left' | 'top' | 'bottom';
        class?: string;
        children?: Snippet;
    } = $props();

    const { open, setOpen } = getContext<SheetContext>(SHEET_CONTEXT);

    const sideClasses: Record<string, string> = {
        right: 'right-0',
        left: 'left-0',
        top: 'top-0',
        bottom: 'bottom-0',
    };

    const sizeClasses: Record<string, string> = {
        right: 'h-full w-80',
        left: 'h-full w-80',
        top: 'h-64 w-full',
        bottom: 'h-64 w-full',
    };

    const close = () => setOpen(false);
</script>

{#if open()}
    <div class="fixed inset-0 z-50">
        <button
            type="button"
            class="fixed inset-0 bg-black/50"
            aria-label="Close"
            onclick={close}
        ></button>
        <div
            class={cn(
                'fixed bg-background p-6 shadow-lg',
                sideClasses[side] ?? sideClasses.right,
                sizeClasses[side] ?? sizeClasses.right,
                className,
            )}
        >
            {@render children?.()}
        </div>
    </div>
{/if}
