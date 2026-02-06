<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', ...rest } = $props();
</script>

<div
    class={cn(
        'flex h-full w-full items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground',
        className,
    )}
    {...rest}
>
    <slot />
</div>
