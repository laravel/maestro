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
    import { leave as leaveTeamAction } from '@/routes/teams';
    import type { Team } from '@/types';

    let {
        team,
        open = $bindable(),
    }: {
        team: Team | null;
        open: boolean;
    } = $props();

    let processing = $state(false);

    const leaveTeam = () => {
        if (!team) {
            return;
        }

        router.visit(leaveTeamAction(team.slug), {
            onStart: () => (processing = true),
            onFinish: () => (processing = false),
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Dialog bind:open>
    <DialogContent>
        <div class="space-y-3">
            <DialogTitle>Leave team</DialogTitle>
            <DialogDescription>
                Are you sure you want to leave <strong>{team?.name}</strong>?
            </DialogDescription>
        </div>

        <DialogFooter class="gap-2">
            <DialogClose>
                <Button variant="secondary">Cancel</Button>
            </DialogClose>

            <Button
                variant="destructive"
                disabled={processing}
                onclick={leaveTeam}
                data-test="leave-team-confirm">Leave team</Button
            >
        </DialogFooter>
    </DialogContent>
</Dialog>
