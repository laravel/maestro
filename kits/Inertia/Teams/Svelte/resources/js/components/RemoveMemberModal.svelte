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
    import type { Team, TeamMember } from '@/types';
    import { destroy as destroyMember } from '@/routes/teams/members';

    let {
        team,
        member,
        open = $bindable(),
    }: {
        team: Team;
        member: TeamMember | null;
        open: boolean;
    } = $props();

    const removeMember = () => {
        if (!member) {
            return;
        }

        router.delete(destroyMember([team.slug, member.id]).url, {
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Dialog bind:open>
    <DialogContent>
        <div class="space-y-3">
            <DialogTitle>Remove team member</DialogTitle>
            <DialogDescription>
                Are you sure you want to remove <strong>{member?.name}</strong> from
                this team?
            </DialogDescription>
        </div>

        <DialogFooter class="gap-2">
            <DialogClose>
                <Button variant="secondary">Cancel</Button>
            </DialogClose>

            <Button variant="destructive" onclick={removeMember}
                >Remove Member</Button
            >
        </DialogFooter>
    </DialogContent>
</Dialog>
