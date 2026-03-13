<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
/* @passkeys */
import { router } from '@inertiajs/vue3';
import { KeyRound } from 'lucide-vue-next';
/* @end-passkeys */
/* @2fa */
import { ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
/* @end-2fa */
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
/* @passkeys */
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
/* @end-passkeys */
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
/* @passkeys */
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
/* @end-passkeys */
import PasswordInput from '@/components/PasswordInput.vue';
/* @2fa */
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
/* @end-2fa */
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
/* @2fa */
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
/* @end-2fa */
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';
/* @2fa */
import { disable, enable } from '@/routes/two-factor';
/* @end-2fa */
import type { BreadcrumbItem } from '@/types';

/* @passkeys */
type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-passkeys */

type Props = {
    _props?: never;
    /* @2fa */
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    /* @end-2fa */
    /* @passkeys */
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
    /* @end-passkeys */
};

withDefaults(defineProps<Props>(), {
    /* @2fa */
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
    /* @end-2fa */
    /* @passkeys */
    canManagePasskeys: false,
    passkeys: () => [],
    /* @end-passkeys */
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security settings',
        href: edit(),
    },
];

/* @2fa */
const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
/* @end-2fa */

/* @passkeys */
const handleDelete = (id: number) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
/* @end-passkeys */
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Security settings" />

        <h1 class="sr-only">Security settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <Form
                    v-bind="SecurityController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Current password"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="New password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            Save password
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <!-- @2fa -->
            <div v-if="canManageTwoFactor" class="space-y-6">
                <Heading
                    variant="small"
                    title="Two-factor authentication"
                    description="Manage your two-factor authentication settings"
                />

                <div
                    v-if="!twoFactorEnabled"
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <p class="text-sm text-muted-foreground">
                        When you enable two-factor authentication, you will be
                        prompted for a secure pin during login. This pin can be
                        retrieved from a TOTP-supported application on your
                        phone.
                    </p>

                    <div>
                        <Button
                            v-if="hasSetupData"
                            @click="showSetupModal = true"
                        >
                            <ShieldCheck />Continue setup
                        </Button>
                        <Form
                            v-else
                            v-bind="enable.form()"
                            @success="showSetupModal = true"
                            #default="{ processing }"
                        >
                            <Button type="submit" :disabled="processing">
                                Enable 2FA
                            </Button>
                        </Form>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <p class="text-sm text-muted-foreground">
                        You will be prompted for a secure, random pin during
                        login, which you can retrieve from the TOTP-supported
                        application on your phone.
                    </p>

                    <div class="relative inline">
                        <Form v-bind="disable.form()" #default="{ processing }">
                            <Button
                                variant="destructive"
                                type="submit"
                                :disabled="processing"
                            >
                                Disable 2FA
                            </Button>
                        </Form>
                    </div>

                    <TwoFactorRecoveryCodes />
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
            <!-- @end-2fa -->

            <!-- @passkeys -->
            <div v-if="canManagePasskeys" class="space-y-6">
                <Heading
                    variant="small"
                    title="Passkeys"
                    description="Manage your passkeys for passwordless sign-in"
                />

                <div class="overflow-hidden rounded-lg border border-border">
                    <template v-if="passkeys.length">
                        <PasskeyItem
                            v-for="passkey in passkeys"
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
            <!-- @end-passkeys -->
        </SettingsLayout>
    </AppLayout>
</template>
