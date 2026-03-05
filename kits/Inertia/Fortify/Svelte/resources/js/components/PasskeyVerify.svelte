<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Passkeys } from '@laravel/passkeys';
    import KeyRound from 'lucide-svelte/icons/key-round';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import { Spinner } from '@/components/ui/spinner';

    let {
        routes,
        label,
        loadingLabel,
        separator,
    }: {
        routes?: {
            options?: string;
            submit?: string;
        };
        label?: string;
        loadingLabel?: string;
        separator?: string;
    } = $props();

    const isSupported =
        typeof window !== 'undefined' && Passkeys.isSupported();

    let isLoading = $state(false);
    let error = $state('');

    async function verify() {
        isLoading = true;
        error = '';

        try {
            const response = await Passkeys.verify(
                routes ? { routes } : undefined,
            );
            const redirect = (response as { redirect?: string }).redirect;
            router.visit(redirect ?? '/dashboard');
        } catch (err) {
            error =
                err instanceof Error
                    ? err.message
                    : 'Unable to verify passkey.';
        } finally {
            isLoading = false;
        }
    }
</script>

{#if isSupported}
    <input type="hidden" autocomplete="webauthn" />

    <div class="grid gap-2">
        <Button
            type="button"
            variant="outline"
            class="w-full"
            onclick={verify}
            disabled={isLoading}
        >
            {#if isLoading}
                <Spinner />
            {:else}
                <KeyRound class="h-4 w-4" />
            {/if}
            {isLoading
                ? (loadingLabel ?? 'Authenticating...')
                : (label ?? 'Sign in with passkey')}
        </Button>

        {#if error}
            <InputError message={error} class="text-center" />
        {/if}
    </div>

    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <Separator class="w-full" />
        </div>
        <div class="relative flex justify-center text-xs uppercase">
            <span class="bg-background px-2 text-muted-foreground">
                {separator ?? 'Or continue with email'}
            </span>
        </div>
    </div>
{/if}
