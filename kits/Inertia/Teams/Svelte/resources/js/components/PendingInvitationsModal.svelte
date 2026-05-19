<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogTitle,
    } from '@/components/ui/dialog';
    import type { DashboardInvitation } from '@/types';

    let {
        invitations,
    }: {
        invitations: DashboardInvitation[];
    } = $props();

    let open = $state(true);
    let processingCode = $state<string | null>(null);

    const acceptInvitation = (invitation: DashboardInvitation) => {
        router.visit(`/invitations/${invitation.code}/accept`, {
            onStart: () => (processingCode = invitation.code),
            onFinish: () => (processingCode = null),
        });
    };

    const declineInvitation = (invitation: DashboardInvitation) => {
        router.delete(`/invitations/${invitation.code}`, {
            onStart: () => (processingCode = invitation.code),
            onFinish: () => (processingCode = null),
            onSuccess: () => {
                if (invitations.length === 1) {
                    open = false;
                }
            },
        });
    };
</script>

<Dialog bind:open>
    <DialogContent>
        <div data-test="pending-invitations-modal" class="space-y-6">
            <div class="space-y-3">
                <DialogTitle>Pending team invitations</DialogTitle>
                <DialogDescription>
                    Accept or decline the teams you have been invited to join.
                </DialogDescription>
            </div>

            <div class="grid gap-4">
                {#each invitations as invitation (invitation.code)}
                    <div
                        data-test="pending-invitation-row"
                        class="rounded-lg border p-4"
                    >
                        <div class="space-y-1">
                            <p class="font-medium">{invitation.teamName}</p>
                            <p class="text-sm text-muted-foreground">
                                {invitation.inviterName} invited you to join this
                                team.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <Button
                                variant="secondary"
                                data-test="pending-invitation-decline"
                                disabled={processingCode === invitation.code}
                                onclick={() => declineInvitation(invitation)}
                            >
                                Decline
                            </Button>

                            <Button
                                data-test="pending-invitation-accept"
                                disabled={processingCode === invitation.code}
                                onclick={() => acceptInvitation(invitation)}
                            >
                                Accept
                            </Button>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    </DialogContent>
</Dialog>
