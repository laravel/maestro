<script lang="ts">
    import { useLayoutProps } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AuthLayout from '@/layouts/auth/AuthSimpleLayout.svelte';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const layout = useLayoutProps({
        title: '',
        description: '',
    });
</script>

<AuthLayout title={layout.title} description={layout.description}>
    {@render children?.()}
</AuthLayout>
