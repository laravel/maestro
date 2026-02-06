<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' } = $props();
</script>

<div data-slot="sidebar-group" data-sidebar="group" class={cn('relative flex w-full min-w-0 flex-col p-2', className)}>
    <slot />
</div>
