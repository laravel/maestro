<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ChevronDown, Mail, UserPlus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type {
    BreadcrumbItem,
    RoleOption,
    Team,
    TeamInvitation,
    TeamMember,
    TeamPermissions,
} from '@/types';
import { destroy, edit, index, update } from '@/routes/teams';
import {
    destroy as destroyInvitation,
    store as storeInvitation,
} from '@/routes/teams/invitations';
import {
    destroy as destroyMember,
    update as updateMember,
} from '@/routes/teams/members';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
    isCurrentTeam: boolean;
    otherTeams: Team[];
};

const props = defineProps<Props>();

const { getInitials } = useInitials();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Teams',
        href: index().url,
    },
    {
        title: props.team.name,
        href: edit(props.team.slug).url,
    },
]);

const inviteDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const removeMemberDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const cancelInvitationDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);
const inviteRole = ref('member');
const confirmationName = ref('');
const newCurrentTeamId = ref('');

const canDeleteTeam = computed(() => {
    const nameMatches = confirmationName.value === props.team.name;
    const hasNewTeamIfNeeded =
        !props.isCurrentTeam || newCurrentTeamId.value !== '';

    return nameMatches && hasNewTeamIfNeeded;
});

const pageTitle = computed(() =>
    props.permissions.canUpdateTeam
        ? `Edit ${props.team.name}`
        : `View ${props.team.name}`,
);

const resetDeleteDialog = () => {
    confirmationName.value = '';
    newCurrentTeamId.value = '';
};

const updateMemberRole = (member: TeamMember, newRole: string) => {
    router.patch(
        updateMember([props.team.slug, member.id]).url,
        {
            role: newRole,
        },
        {
            preserveScroll: true,
        },
    );
};

const confirmRemoveMember = (member: TeamMember) => {
    memberToRemove.value = member;
    removeMemberDialogOpen.value = true;
};

const removeMember = () => {
    if (memberToRemove.value) {
        router.delete(
            destroyMember([props.team.slug, memberToRemove.value.id]).url,
            {
                onSuccess: () => {
                    removeMemberDialogOpen.value = false;
                    memberToRemove.value = null;
                },
            },
        );
    }
};

const confirmCancelInvitation = (invitation: TeamInvitation) => {
    invitationToCancel.value = invitation;
    cancelInvitationDialogOpen.value = true;
};

const cancelInvitation = () => {
    if (invitationToCancel.value) {
        router.delete(
            destroyInvitation([props.team.slug, invitationToCancel.value.code])
                .url,
            {
                onSuccess: () => {
                    cancelInvitationDialogOpen.value = false;
                    invitationToCancel.value = null;
                },
            },
        );
    }
};

const deleteTeam = () => {
    router.delete(destroy(props.team.slug).url, {
        data: {
            name: confirmationName.value,
            new_current_team_id:
                newCurrentTeamId.value === ''
                    ? null
                    : Number(newCurrentTeamId.value),
        },
        onSuccess: () => {
            deleteDialogOpen.value = false;
            resetDeleteDialog();
        },
        onError: () => {
            // Keep the dialog open on validation errors
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="pageTitle" />

        <h1 class="sr-only">{{ pageTitle }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10">
                <!-- Team Name Section -->
                <div v-if="permissions.canUpdateTeam" class="space-y-6">
                    <Heading
                        variant="small"
                        title="Team Settings"
                        description="Update your team name and settings"
                    />

                    <Form
                        v-bind="update.form(team.slug)"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Team Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="team.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="processing">
                                Save
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </div>

                <div v-else class="space-y-6">
                    <Heading variant="small" :title="team.name" />
                </div>

                <!-- Members Section -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Team Members"
                            :description="
                                permissions.canCreateInvitation
                                    ? 'Manage who has access to this team'
                                    : ''
                            "
                        />

                        <Dialog
                            v-model:open="inviteDialogOpen"
                            v-if="permissions.canCreateInvitation"
                        >
                            <DialogTrigger as-child>
                                <Button> <UserPlus /> Invite Member </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="storeInvitation.form(team.slug)"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="inviteDialogOpen = false"
                                >
                                    <DialogHeader>
                                        <DialogTitle
                                            >Invite a team member</DialogTitle
                                        >
                                        <DialogDescription>
                                            Send an invitation to join this
                                            team.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4">
                                        <div class="grid gap-2">
                                            <Label for="email"
                                                >Email Address</Label
                                            >
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                placeholder="colleague@example.com"
                                                required
                                            />
                                            <InputError
                                                :message="errors.email"
                                            />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="role">Role</Label>
                                            <Select
                                                v-model="inviteRole"
                                                name="role"
                                            >
                                                <SelectTrigger class="w-full">
                                                    <SelectValue
                                                        placeholder="Select a role"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem
                                                        v-for="role in availableRoles"
                                                        :key="role.value"
                                                        :value="role.value"
                                                    >
                                                        {{ role.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                :message="errors.role"
                                            />
                                        </div>
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            type="submit"
                                            :disabled="processing"
                                        >
                                            Send Invitation
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex items-center gap-4">
                                <Avatar class="h-10 w-10">
                                    <AvatarImage
                                        v-if="member.avatar"
                                        :src="member.avatar"
                                        :alt="member.name"
                                    />
                                    <AvatarFallback>{{
                                        getInitials(member.name)
                                    }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium">
                                        {{ member.name }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ member.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <DropdownMenu
                                    v-if="
                                        member.role !== 'owner' &&
                                        permissions.canUpdateMember
                                    "
                                >
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm">
                                            {{ member.role_label }}
                                            <ChevronDown
                                                class="ml-2 h-4 w-4 opacity-50"
                                            />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem
                                            v-for="role in availableRoles"
                                            :key="role.value"
                                            @click="
                                                updateMemberRole(
                                                    member,
                                                    role.value,
                                                )
                                            "
                                        >
                                            {{ role.label }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Badge v-else variant="secondary">
                                    {{ member.role_label }}
                                </Badge>

                                <TooltipProvider
                                    v-if="
                                        member.role !== 'owner' &&
                                        permissions.canRemoveMember
                                    "
                                >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    confirmRemoveMember(member)
                                                "
                                            >
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Remove member</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Invitations Section -->
                <div v-if="invitations.length > 0" class="space-y-6">
                    <Heading
                        variant="small"
                        title="Pending Invitations"
                        description="Invitations that haven't been accepted yet"
                    />

                    <div class="space-y-3">
                        <div
                            v-for="invitation in invitations"
                            :key="invitation.code"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"
                                >
                                    <Mail
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                </div>
                                <div>
                                    <div class="font-medium">
                                        {{ invitation.email }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ invitation.role_label }}
                                    </div>
                                </div>
                            </div>

                            <TooltipProvider
                                v-if="permissions.canCancelInvitation"
                            >
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="
                                                confirmCancelInvitation(
                                                    invitation,
                                                )
                                            "
                                        >
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Cancel invitation</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div
                    v-if="permissions.canDeleteTeam && !team.isPersonal"
                    class="space-y-6"
                >
                    <Heading
                        variant="small"
                        title="Delete team"
                        description="Delete your team and remove all of its members"
                    />
                    <div
                        class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                    >
                        <div
                            class="relative space-y-0.5 text-red-600 dark:text-red-100"
                        >
                            <p class="font-medium">Warning</p>
                            <p class="text-sm">
                                Please proceed with caution, this cannot be
                                undone.
                            </p>
                        </div>
                        <Dialog
                            v-model:open="deleteDialogOpen"
                            @update:open="
                                (open) => !open && resetDeleteDialog()
                            "
                        >
                            <DialogTrigger as-child>
                                <Button variant="destructive"
                                    >Delete team</Button
                                >
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Are you sure?</DialogTitle>
                                    <DialogDescription>
                                        This action cannot be undone. This will
                                        permanently delete the team
                                        <strong>{{ team.name }}</strong> and
                                        remove all of its members.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="space-y-4 py-4">
                                    <div class="grid gap-2">
                                        <Label for="confirmation-name">
                                            Type
                                            <strong>{{ team.name }}</strong> to
                                            confirm
                                        </Label>
                                        <Input
                                            id="confirmation-name"
                                            v-model="confirmationName"
                                            placeholder="Enter team name"
                                            autocomplete="off"
                                        />
                                    </div>

                                    <div
                                        v-if="
                                            isCurrentTeam &&
                                            otherTeams.length > 0
                                        "
                                        class="grid gap-2"
                                    >
                                        <Label for="new-current-team">
                                            Select a new current team
                                        </Label>
                                        <Select v-model="newCurrentTeamId">
                                            <SelectTrigger class="w-full">
                                                <SelectValue
                                                    placeholder="Select a team"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="otherTeam in otherTeams"
                                                    :key="otherTeam.id"
                                                    :value="
                                                        String(otherTeam.id)
                                                    "
                                                >
                                                    {{ otherTeam.name }}
                                                    <span
                                                        v-if="
                                                            otherTeam.isPersonal
                                                        "
                                                        class="ml-2 text-muted-foreground"
                                                        >(Personal)</span
                                                    >
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            You are deleting your current team.
                                            Please select which team to switch
                                            to.
                                        </p>
                                    </div>

                                    <div
                                        v-else-if="
                                            isCurrentTeam &&
                                            otherTeams.length === 0
                                        "
                                        class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200"
                                    >
                                        You cannot delete your current team
                                        because you have no other teams to
                                        switch to. Please create or join another
                                        team first.
                                    </div>
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button
                                        variant="destructive"
                                        :disabled="!canDeleteTeam"
                                        @click="deleteTeam"
                                    >
                                        Delete Team
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>

            <!-- Remove Member Confirmation Dialog -->
            <Dialog v-model:open="removeMemberDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove team member</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove
                            <strong>{{ memberToRemove?.name }}</strong> from
                            this team?
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

            <!-- Cancel Invitation Confirmation Dialog -->
            <Dialog v-model:open="cancelInvitationDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cancel invitation</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to cancel the invitation for
                            <strong>{{ invitationToCancel?.email }}</strong
                            >?
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">
                                Keep Invitation
                            </Button>
                        </DialogClose>

                        <Button variant="destructive" @click="cancelInvitation">
                            Cancel Invitation
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </SettingsLayout>
    </AppLayout>
</template>
