<script lang="ts">
    import { router } from '@inertiajs/svelte';
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
    import type { Team } from '@/types';
    import { destroy } from '@/routes/teams';

    let {
        team,
        isCurrentTeam,
        otherTeams,
        open = $bindable(),
    }: {
        team: Team;
        isCurrentTeam: boolean;
        otherTeams: Team[];
        open: boolean;
    } = $props();

    let confirmationName = $state('');
    let newCurrentTeamId = $state('');

    const canDeleteTeam = $derived(
        confirmationName === team.name && (!isCurrentTeam || newCurrentTeamId !== ''),
    );

    const selectedNewCurrentTeam = $derived(
        otherTeams.find((otherTeam) => String(otherTeam.id) === newCurrentTeamId),
    );

    const resetDialog = () => {
        confirmationName = '';
        newCurrentTeamId = '';
    };

    $effect(() => {
        if (!open) {
            resetDialog();
        }
    });

    const deleteTeam = () => {
        router.delete(destroy(team.slug).url, {
            data: {
                name: confirmationName,
                new_current_team_id: newCurrentTeamId === '' ? null : Number(newCurrentTeamId),
            },
            onSuccess: () => (open = false),
        });
    };
</script>

<Dialog bind:open>
    <DialogContent>
        <div class="space-y-3">
            <DialogTitle>Are you sure?</DialogTitle>
            <DialogDescription>
                This action cannot be undone. This will permanently delete the team
                <strong>{team.name}</strong> and remove all of its members.
            </DialogDescription>
        </div>

        <div class="space-y-4 py-4">
            <div class="grid gap-2">
                <Label for="confirmation-name">
                    Type <strong>{team.name}</strong> to confirm
                </Label>
                <Input
                    id="confirmation-name"
                    value={confirmationName}
                    oninput={(event) =>
                        (confirmationName = (event.currentTarget as HTMLInputElement).value)}
                    placeholder="Enter team name"
                    autocomplete="off"
                />
            </div>

            {#if isCurrentTeam && otherTeams.length > 0}
                <div class="grid gap-2">
                    <Label for="new-current-team">Select a new current team</Label>

                    <Select bind:value={newCurrentTeamId}>
                        <SelectTrigger class="w-full">
                            {selectedNewCurrentTeam?.name ?? 'Select a team'}
                        </SelectTrigger>
                        <SelectContent>
                            {#each otherTeams as otherTeam (otherTeam.id)}
                                <SelectItem
                                    value={String(otherTeam.id)}
                                    label={otherTeam.name}
                                >
                                    {otherTeam.name}
                                    {#if otherTeam.isPersonal}
                                        <span class="ml-2 text-muted-foreground">(Personal)</span>
                                    {/if}
                                </SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>

                    <p class="text-sm text-muted-foreground">
                        You are deleting your current team. Please select which team to switch to.
                    </p>
                </div>
            {:else if isCurrentTeam && otherTeams.length === 0}
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200">
                    You cannot delete your current team because you have no other teams to switch to. Please create or join another team first.
                </div>
            {/if}
        </div>

        <DialogFooter class="gap-2">
            <DialogClose>
                <Button variant="secondary" onclick={resetDialog}>Cancel</Button>
            </DialogClose>

            <Button variant="destructive" disabled={!canDeleteTeam} onclick={deleteTeam}>
                Delete Team
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
