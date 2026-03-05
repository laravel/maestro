<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import KeyRound from 'lucide-svelte/icons/key-round';
    import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import PasskeyItem from '@/components/PasskeyItem.svelte';
    import PasskeyRegister from '@/components/PasskeyRegister.svelte';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import { index } from '@/routes/passkeys';
    import type { BreadcrumbItem } from '@/types';

    type Passkey = {
        id: number;
        name: string;
        authenticator: string | null;
        created_at_diff: string;
        last_used_at_diff: string | null;
    };

    let {
        passkeys,
    }: {
        passkeys: Passkey[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Passkeys',
            href: index(),
        },
    ];

    function handleDelete(id: number) {
        router.delete(destroy.url(id), {
            preserveScroll: true,
        });
    }

    function handleRegisterSuccess() {
        router.reload();
    }
</script>

<AppHead title="Passkeys" />

<AppLayout {breadcrumbs}>
    <h1 class="sr-only">Passkey settings</h1>

    <SettingsLayout>
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Passkeys"
                description="Manage your passkeys for passwordless sign-in"
            />

            <div class="overflow-hidden rounded-lg border border-border">
                {#if passkeys.length > 0}
                    {#each passkeys as passkey (passkey.id)}
                        <PasskeyItem {passkey} onDelete={handleDelete} />
                    {/each}
                {:else}
                    <div class="p-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                        >
                            <KeyRound class="h-7 w-7 text-muted-foreground" />
                        </div>
                        <p class="font-medium">No passkeys yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Add a passkey to sign in without a password
                        </p>
                    </div>
                {/if}
            </div>

            <PasskeyRegister onSuccess={handleRegisterSuccess} />
        </div>
    </SettingsLayout>
</AppLayout>
