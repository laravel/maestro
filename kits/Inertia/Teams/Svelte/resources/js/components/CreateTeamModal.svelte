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
</script>

<Dialog bind:open>
    <DialogTrigger asChild>
        {#snippet children(props)}
            {@render trigger?.(props)}
        {/snippet}
    </DialogTrigger>
    <DialogContent>
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
                    <Label for="name">Team Name</Label>
                    <Input
                        id="name"
                        name="name"
                        placeholder="My Team"
                        required
                    />
                    <InputError message={errors.name} />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button type="submit" disabled={processing}
                        >Create Team</Button
                    >
                </DialogFooter>
            {/snippet}
        </Form>
    </DialogContent>
</Dialog>
