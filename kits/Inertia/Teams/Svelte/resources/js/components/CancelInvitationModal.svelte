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
    import type { Team, TeamInvitation } from '@/types';
    import { destroy as destroyInvitation } from '@/routes/teams/invitations';

    let {
        team,
        invitation,
        open = $bindable(),
    }: {
        team: Team;
        invitation: TeamInvitation | null;
        open: boolean;
    } = $props();

    const cancelInvitation = () => {
        if (!invitation) {
            return;
        }

        router.delete(destroyInvitation([team.slug, invitation.code]).url, {
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Dialog bind:open>
    <DialogContent>
        <div class="space-y-3">
            <DialogTitle>Cancel invitation</DialogTitle>
            <DialogDescription class="mb-3">
                Are you sure you want to cancel the invitation for <strong
                    >{invitation?.email}</strong
                >?
            </DialogDescription>
        </div>

        <DialogFooter class="gap-2">
            <DialogClose>
                <Button variant="secondary">Keep Invitation</Button>
            </DialogClose>

            <Button variant="destructive" onclick={cancelInvitation}
                >Cancel Invitation</Button
            >
        </DialogFooter>
    </DialogContent>
</Dialog>
