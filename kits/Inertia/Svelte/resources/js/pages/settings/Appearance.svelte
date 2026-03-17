<script lang="ts">
    import { setLayoutProps } from '@inertiajs/svelte';
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { edit as editAppearance } from '@/routes/appearance';

    setLayoutProps({
        breadcrumbs: [{ title: 'Appearance settings', href: editAppearance() }],
    });
</script>

<AppHead title="Appearance settings" />

<h1 class="sr-only">Appearance settings</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Appearance settings"
        description="Update your account's appearance settings"
    />
    <AppearanceTabs />
</div>
