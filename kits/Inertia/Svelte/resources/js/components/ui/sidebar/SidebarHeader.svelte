<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' } = $props();
</script>

<div class={cn('flex flex-col', className)}>
    <slot />
</div>
