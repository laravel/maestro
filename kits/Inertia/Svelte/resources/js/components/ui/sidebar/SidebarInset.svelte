<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' } = $props();
</script>

<main
    data-slot="sidebar-inset"
    class={cn(
        'bg-background relative flex w-full flex-1 flex-col',
        'md:peer-data-[variant=inset]:m-2 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow-sm md:peer-data-[variant=inset]:peer-data-[state=collapsed]:ml-2',
        className,
    )}
>
    <slot />
</main>
