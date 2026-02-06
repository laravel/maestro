<script lang="ts">
    import { cn } from '@/lib/utils';
    let { class: className = '' } = $props();
</script>

<div class={cn('px-2 py-1.5 text-sm font-semibold', className)}>
    <slot />
</div>
