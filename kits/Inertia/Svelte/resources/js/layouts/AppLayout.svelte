<script lang="ts">
    import { useLayoutProps } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppLayout from '@/layouts/app/AppSidebarLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const layout = useLayoutProps({
        breadcrumbs: [] as BreadcrumbItem[],
    });
</script>

<AppLayout breadcrumbs={layout.breadcrumbs}>
    {@render children?.()}
</AppLayout>
