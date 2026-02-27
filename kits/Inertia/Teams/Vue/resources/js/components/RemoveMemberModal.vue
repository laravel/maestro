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
import { destroy as destroyMember } from '@/routes/teams/members';
import type { Team, TeamMember } from '@/types';

type Props = {
    team: Team;
    member: TeamMember | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const removeMember = () => {
    if (!props.member) {
        return;
    }

    router.delete(destroyMember([props.team.slug, props.member.id]).url, {
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Remove team member</DialogTitle>
                <DialogDescription>
                    Are you sure you want to remove
                    <strong>{{ props.member?.name }}</strong> from this team?
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary"> Cancel </Button>
                </DialogClose>

                <Button variant="destructive" @click="removeMember">
                    Remove Member
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
