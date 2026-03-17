<script setup lang="ts">
import { useLayoutProps } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const layout = useLayoutProps({
    title: '',
    description: '',
});
</script>

<template>
    <AuthLayout :title="layout.title" :description="layout.description">
        <slot />
    </AuthLayout>
</template>
