<script lang="ts">
    import { Form, setLayoutProps } from '@inertiajs/svelte';
    import { useLang } from '@/lib/useLang';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import PasswordInput from '@/components/PasswordInput.svelte';
    import TextLink from '@/components/TextLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Spinner } from '@/components/ui/spinner';
    /* @chisel-registration */
    import { register } from '@/routes';
    /* @end-chisel-registration */
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';
    /* @chisel-passkeys */
    import PasskeyVerify from '@/components/PasskeyVerify.svelte';
    /* @end-chisel-passkeys */

    let {
        status = '',
        canResetPassword,
    }: {
        status?: string;
        canResetPassword: boolean;
    } = $props();

    const { __ } = useLang();

    setLayoutProps({
        title: __('Log in to your account'),
        description: __('Enter your email and password below to log in'),
    });
</script>

<AppHead title={__('Log in')} />

{#if status}
    <div class="mb-4 text-center text-sm font-medium text-green-600">
        {status}
    </div>
{/if}

<!-- @chisel-passkeys -->
<PasskeyVerify />
<!-- @end-chisel-passkeys -->

<Form
    {...store.form()}
    resetOnSuccess={['password']}
    class="flex flex-col gap-6"
>
    {#snippet children({ errors, processing })}
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">{__('Email address')}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError message={errors.email} />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">{__('Password')}</Label>
                    {#if canResetPassword}
                        <TextLink href={request()} class="text-sm">
                            {__('Forgot your password?')}
                        </TextLink>
                    {/if}
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                />
                <InputError message={errors.password} />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" />
                    <span>{__('Remember me')}</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                disabled={processing}
                data-test="login-button"
            >
                {#if processing}<Spinner />{/if}
                {__('Log in')}
            </Button>
        </div>

        <!-- @chisel-registration -->
        <div class="text-center text-sm text-muted-foreground">
            {__("Don't have an account?")}
            <TextLink href={register()}>{__('Sign up')}</TextLink>
        </div>
        <!-- @end-chisel-registration -->
    {/snippet}
</Form>
