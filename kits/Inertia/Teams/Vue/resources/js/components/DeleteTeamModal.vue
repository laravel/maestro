<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team;
    isCurrentTeam: boolean;
    otherTeams: Team[];
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const confirmationName = ref('');
const newCurrentTeamId = ref('');
const processing = ref(false);

const canDeleteTeam = computed(() => {
    const nameMatches = confirmationName.value === props.team.name;
    const hasNewTeamIfNeeded =
        !props.isCurrentTeam || newCurrentTeamId.value !== '';

    return nameMatches && hasNewTeamIfNeeded;
});

const resetDialog = () => {
    confirmationName.value = '';
    newCurrentTeamId.value = '';
    processing.value = false;
};

const handleOpenChange = (nextOpen: boolean) => {
    emit('update:open', nextOpen);

    if (!nextOpen) {
        resetDialog();
    }
};

const deleteTeam = () => {
    router.visit(destroy(props.team.slug), {
        data: {
            name: confirmationName.value,
            new_current_team_id:
                newCurrentTeamId.value === ''
                    ? null
                    : Number(newCurrentTeamId.value),
        },
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => handleOpenChange(false),
    });
};
</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Are you sure?</DialogTitle>
                <DialogDescription>
                    This action cannot be undone. This will permanently delete
                    the team <strong>"{{ props.team.name }}"</strong> and remove
                    all of its members.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div class="grid gap-2">
                    <Label for="confirmation-name">
                        Type <strong>"{{ props.team.name }}"</strong> to confirm
                    </Label>
                    <Input
                        id="confirmation-name"
                        v-model="confirmationName"
                        placeholder="Enter team name"
                        autocomplete="off"
                    />
                </div>

                <div
                    v-if="props.isCurrentTeam && props.otherTeams.length > 0"
                    class="grid gap-2"
                >
                    <Label for="new-current-team">
                        Select a new current team
                    </Label>
                    <Select v-model="newCurrentTeamId">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Select a team" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="otherTeam in props.otherTeams"
                                :key="otherTeam.id"
                                :value="String(otherTeam.id)"
                            >
                                {{ otherTeam.name }}
                                <span
                                    v-if="otherTeam.isPersonal"
                                    class="ml-2 text-muted-foreground"
                                    >(Personal)</span
                                >
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-sm text-muted-foreground">
                        You are deleting your current team. Please select which
                        team to switch to.
                    </p>
                </div>

                <div
                    v-else-if="
                        props.isCurrentTeam && props.otherTeams.length === 0
                    "
                    class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200"
                >
                    You cannot delete your current team because you have no
                    other teams to switch to. Please create or join another team
                    first.
                </div>
            </div>

            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary"> Cancel </Button>
                </DialogClose>

                <Button
                    variant="destructive"
                    :disabled="!canDeleteTeam || processing"
                    @click="deleteTeam"
                >
                    Delete team
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
