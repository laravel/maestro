<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import Heading from '@/components/Heading.vue';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index } from '@/routes/passkeys';
import type { BreadcrumbItem } from '@/types';

type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

const props = defineProps<{
    passkeys: Passkey[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Passkeys',
        href: index(),
    },
];

const handleDelete = (id: number) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Passkeys" />

        <h1 class="sr-only">Passkey settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Passkeys"
                    description="Manage your passkeys for passwordless sign-in"
                />

                <div class="overflow-hidden rounded-lg border border-border">
                    <template v-if="props.passkeys.length">
                        <PasskeyItem
                            v-for="passkey in props.passkeys"
                            :key="passkey.id"
                            :passkey="passkey"
                            @remove="handleDelete"
                        />
                    </template>

                    <div v-else class="p-8 text-center">
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
                </div>

                <PasskeyRegister @success="handleRegisterSuccess" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
