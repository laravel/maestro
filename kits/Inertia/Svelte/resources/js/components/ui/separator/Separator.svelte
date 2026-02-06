<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', orientation = 'horizontal', ...rest } = $props();

    const base =
        orientation === 'vertical'
            ? 'h-full w-px bg-border'
            : 'h-px w-full bg-border';
</script>

<div class={cn('shrink-0', base, className)} {...rest} />
