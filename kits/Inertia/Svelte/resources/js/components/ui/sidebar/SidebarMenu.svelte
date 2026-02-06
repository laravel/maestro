<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' } = $props();
</script>

<ul data-slot="sidebar-menu" data-sidebar="menu" class={cn('flex w-full min-w-0 flex-col gap-1', className)}>
    <slot />
</ul>
