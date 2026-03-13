<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
/* @passkeys */
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
/* @end-passkeys */
import InputError from '@/components/InputError.vue';
/* @passkeys */
import PasskeyVerify from '@/components/PasskeyVerify.vue';
/* @end-passkeys */
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <!-- @passkeys -->
        <PasskeyVerify
            :routes="{
                options: confirmOptions.url(),
                submit: confirmStore.url(),
            }"
            label="Confirm with passkey"
            loading-label="Confirming..."
            separator="Or confirm with password"
        />
        <!-- @end-passkeys -->

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" />
                        Confirm password
                    </Button>
                </div>
            </div>
        </Form>
    </AuthLayout>
</template>
