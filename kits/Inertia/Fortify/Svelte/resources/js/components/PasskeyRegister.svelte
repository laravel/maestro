<script lang="ts">
    import { usePasskeyRegister } from '@laravel/passkeys/svelte';
    import Plus from 'lucide-svelte/icons/plus';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let {
        onSuccess,
    }: {
        onSuccess?: () => void;
    } = $props();

    let name = $state('');
    let showForm = $state(false);
    const { register, isLoading, error, isSupported } = usePasskeyRegister({
        onSuccess: () => {
            name = '';
            showForm = false;
            onSuccess?.();
        },
    });

    const handleSubmit = async (event: SubmitEvent) => {
        event.preventDefault();

        if (!name.trim()) {
            return;
        }

        await register(name.trim());
    };

    const handleCancel = () => {
        showForm = false;
        name = '';
    };
</script>

{#if !isSupported}
    <div class="text-sm text-muted-foreground">
        Passkeys are not supported in this browser.
    </div>
{:else if !showForm}
    <Button onclick={() => (showForm = true)}>
        <Plus class="h-4 w-4" />
        Add passkey
    </Button>
{:else}
    <form
        onsubmit={handleSubmit}
        class="space-y-4 rounded-lg border border-border bg-muted/50 p-4"
    >
        <div class="space-y-2">
            <Label for="passkey-name">Passkey name</Label>
            <Input
                id="passkey-name"
                type="text"
                bind:value={name}
                placeholder="e.g., MacBook Pro, iPhone"
                autofocus
            />
            <p class="text-xs text-muted-foreground">
                Give this passkey a name to help you identify it later
            </p>
        </div>

        {#if error}
            <InputError message={error} />
        {/if}

        <div class="flex gap-2">
            <Button type="submit" disabled={isLoading || !name.trim()}>
                {isLoading ? 'Registering...' : 'Register passkey'}
            </Button>
            <Button type="button" variant="ghost" onclick={handleCancel}>
                Cancel
            </Button>
        </div>
    </form>
{/if}
