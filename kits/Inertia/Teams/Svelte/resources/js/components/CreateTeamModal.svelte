<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
        DialogTrigger,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { store } from '@/routes/teams';

    type TriggerProps = {
        onClick?: (event: MouseEvent) => void;
        [key: string]: unknown;
    };

    let {
        children: trigger,
    }: {
        children?: Snippet<[TriggerProps]>;
    } = $props();

    let open = $state(false);
    let formKey = $state(0);

    function handleOpenChange(value: boolean) {
        open = value;

        if (!value) {
            formKey++;
        }
    }
</script>

<Dialog open={open} onOpenChange={handleOpenChange}>
    <DialogTrigger asChild>
        {#snippet children(props)}
            {@render trigger?.(props)}
        {/snippet}
    </DialogTrigger>
    <DialogContent>
        {#key formKey}
        <Form
            {...store.form()}
            class="space-y-6"
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <div class="space-y-3">
                    <DialogTitle>Create a new team</DialogTitle>
                    <DialogDescription>
                        Create a new team to collaborate with others.
                    </DialogDescription>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Team name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="My team"
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button type="submit" disabled={processing}
                        >Create team</Button
                    >
                </DialogFooter>
            {/snippet}
        </Form>
        {/key}
    </DialogContent>
</Dialog>
