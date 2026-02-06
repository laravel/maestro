<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' } = $props();
</script>

<div class={cn('flex min-h-0 flex-1 flex-col gap-2 overflow-auto', className)}>
    <slot />
</div>
