<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ShieldBan from 'lucide-svelte/icons/shield-ban';
    import ShieldCheck from 'lucide-svelte/icons/shield-check';
    import { onDestroy } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.svelte';
    import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import { twoFactorAuthState } from '@/lib/twoFactorAuth.svelte';
    import type { BreadcrumbItem } from '@/types';
    import { disable, enable, show } from '@/routes/two-factor';

    let {
        requiresConfirmation = false,
        twoFactorEnabled = false,
    }: {
        requiresConfirmation?: boolean;
        twoFactorEnabled?: boolean;
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Two-Factor Authentication',
            href: show.url(),
        },
    ];

    const twoFactorAuth = twoFactorAuthState();
    let showSetupModal = $state(false);

    onDestroy(() => {
        twoFactorAuth.clearTwoFactorAuthData();
    });
</script>

<AppHead title="Two-Factor Authentication" />

<AppLayout breadcrumbs={breadcrumbs}>
    <h1 class="sr-only">Two-Factor Authentication Settings</h1>

    <SettingsLayout>
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Two-Factor Authentication"
                description="Manage your two-factor authentication settings"
            />

            {#if !twoFactorEnabled}
                <div class="flex flex-col items-start justify-start space-y-4">
                    <Badge variant="destructive">Disabled</Badge>

                    <p class="text-muted-foreground">
                        When you enable two-factor authentication, you will be prompted for a secure pin during login. This
                        pin can be retrieved from a TOTP-supported application on your phone.
                    </p>

                    <div>
                        {#if twoFactorAuth.hasSetupData()}
                            <Button onclick={() => (showSetupModal = true)}>
                                <ShieldCheck class="size-4" />Continue Setup
                            </Button>
                        {:else}
                            <Form
                                {...enable.form()}
                                onSuccess={() => (showSetupModal = true)}
                            >
                                {#snippet children({ processing })}
                                    <Button type="submit" disabled={processing}>
                                        <ShieldCheck class="size-4" />Enable 2FA
                                    </Button>
                                {/snippet}
                            </Form>
                        {/if}
                    </div>
                </div>
            {:else}
                <div class="flex flex-col items-start justify-start space-y-4">
                    <Badge variant="default">Enabled</Badge>

                    <p class="text-muted-foreground">
                        With two-factor authentication enabled, you will be prompted for a secure, random pin during login,
                        which you can retrieve from the TOTP-supported application on your phone.
                    </p>

                    <TwoFactorRecoveryCodes />

                    <div class="relative inline">
                        <Form {...disable.form()}>
                            {#snippet children({ processing })}
                                <Button variant="destructive" type="submit" disabled={processing}>
                                    <ShieldBan class="size-4" />
                                    Disable 2FA
                                </Button>
                            {/snippet}
                        </Form>
                    </div>
                </div>
            {/if}

            <TwoFactorSetupModal
                bind:isOpen={showSetupModal}
                {requiresConfirmation}
                {twoFactorEnabled}
            />
        </div>
    </SettingsLayout>
</AppLayout>
