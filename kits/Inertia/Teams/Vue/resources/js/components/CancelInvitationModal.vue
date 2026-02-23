<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Team, TeamInvitation } from '@/types';
import { destroy as destroyInvitation } from '@/routes/teams/invitations';

type Props = {
    team: Team;
    invitation: TeamInvitation | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const cancelInvitation = () => {
    if (!props.invitation) {
        return;
    }

    router.delete(
        destroyInvitation([props.team.slug, props.invitation.code]).url,
        {
            onSuccess: () => emit('update:open', false),
        },
    );
};
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cancel invitation</DialogTitle>
                <DialogDescription>
                    Are you sure you want to cancel the invitation for
                    <strong>{{ props.invitation?.email }}</strong
                    >?
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary"> Keep Invitation </Button>
                </DialogClose>

                <Button variant="destructive" @click="cancelInvitation">
                    Cancel Invitation
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
