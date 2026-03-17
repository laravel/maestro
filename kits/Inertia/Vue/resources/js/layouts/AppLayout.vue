<script setup lang="ts">
import { useLayoutProps } from '@inertiajs/vue3';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

const layout = useLayoutProps({
    breadcrumbs: [] as BreadcrumbItem[],
});
</script>

<template>
    <AppLayout :breadcrumbs="layout.breadcrumbs">
        <slot />
    </AppLayout>
</template>
