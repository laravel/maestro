<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', ...rest } = $props();
</script>

<label
    class={cn(
        'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
        className,
    )}
    {...rest}
>
    <slot />
</label>
