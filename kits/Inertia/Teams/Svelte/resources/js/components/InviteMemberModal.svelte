<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import { store as storeInvitation } from '@/routes/teams/invitations';
    import type { RoleOption, Team } from '@/types';

    let {
        team,
        availableRoles,
        open = $bindable(),
    }: {
        team: Team;
        availableRoles: RoleOption[];
        open: boolean;
    } = $props();

    let inviteRole = $state<RoleOption['value']>('member');
    let formKey = $state(0);

    const inviteRoleLabel = $derived(
        availableRoles.find((role) => role.value === inviteRole)?.label ??
            'Select a role',
    );

    function handleOpenChange(value: boolean) {
        open = value;

        if (!value) {
            inviteRole = 'member';
            formKey++;
        }
    }
</script>

<Dialog open={open} onOpenChange={handleOpenChange}>
    <DialogContent>
        {#key formKey}
        <Form
            {...storeInvitation.form(team.slug)}
            class="space-y-6"
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <div class="space-y-3">
                    <DialogTitle>Invite a team member</DialogTitle>
                    <DialogDescription>
                        Send an invitation to join this team.
                    </DialogDescription>
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="colleague@example.com"
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <Select bind:value={inviteRole}>
                            <SelectTrigger class="w-full">
                                {inviteRoleLabel}
                            </SelectTrigger>
                            <SelectContent>
                                {#each availableRoles as role (role.value)}
                                    <SelectItem
                                        value={role.value}
                                        label={role.label}
                                    >
                                        {role.label}
                                    </SelectItem>
                                {/each}
                            </SelectContent>
                        </Select>

                        <input type="hidden" name="role" value={inviteRole} />
                        <InputError message={errors.role} />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button type="submit" disabled={processing}
                        >Send invitation</Button
                    >
                </DialogFooter>
            {/snippet}
        </Form>
        {/key}
    </DialogContent>
</Dialog>
