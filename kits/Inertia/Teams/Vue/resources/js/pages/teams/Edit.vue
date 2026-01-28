<script setup lang="ts">
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
import { Form, Head, router } from '@inertiajs/vue3';
import { ChevronDown, Mail, Trash2, UserPlus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
};

const props = defineProps<Props>();

const { getInitials } = useInitials();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.slug}`,
    },
]);

const inviteDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const removeMemberDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const cancelInvitationDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);
const inviteRole = ref('member');

const inviteRoleLabel = computed(() => {
    const role = props.availableRoles.find((r) => r.value === inviteRole.value);
    return role?.label ?? inviteRole.value;
});

const updateMemberRole = (member: TeamMember, newRole: string) => {
    router.patch(`/teams/${props.team.slug}/members/${member.id}`, {
        role: newRole,
    });
};

const confirmRemoveMember = (member: TeamMember) => {
    memberToRemove.value = member;
    removeMemberDialogOpen.value = true;
};

const removeMember = () => {
    if (memberToRemove.value) {
        router.delete(
            `/teams/${props.team.slug}/members/${memberToRemove.value.id}`,
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
        router.delete(`/teams/${props.team.slug}/invitations/${invitationToCancel.value.id}`, {
            onSuccess: () => {
                cancelInvitationDialogOpen.value = false;
                invitationToCancel.value = null;
            },
        });
    }
};

const deleteTeam = () => {
    router.delete(`/teams/${props.team.slug}`, {
        onSuccess: () => {
            deleteDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${team.name}`" />

        <h1 class="sr-only">Edit Team: {{ team.name }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10">
                <!-- Team Name Section -->
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Team Settings"
                        description="Update your team name and settings"
                    />

                    <Form
                        :action="`/teams/${team.slug}`"
                        method="patch"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Team Name</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="team.name"
                                :disabled="!permissions.canUpdateTeam"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                type="submit"
                                :disabled="processing || !permissions.canUpdateTeam"
                            >
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

                <!-- Members Section -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Team Members"
                            description="Manage who has access to this team"
                        />

                        <Dialog v-model:open="inviteDialogOpen" v-if="permissions.canCreateInvitation">
                            <DialogTrigger as-child>
                                <Button>
                                    <UserPlus class="mr-2 h-4 w-4" />
                                    Invite Member
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    :action="`/teams/${team.slug}/invitations`"
                                    method="post"
                                    class="space-y-6"
                                    v-slot="{ errors, processing }"
                                    @success="inviteDialogOpen = false"
                                >
                                    <DialogHeader>
                                        <DialogTitle>Invite a team member</DialogTitle>
                                        <DialogDescription>
                                            Send an invitation to join this team.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4">
                                        <div class="grid gap-2">
                                            <Label for="email">Email Address</Label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                placeholder="colleague@example.com"
                                                required
                                            />
                                            <InputError :message="errors.email" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="role">Role</Label>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="outline" class="w-full justify-between">
                                                        {{ inviteRoleLabel }}
                                                        <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent class="w-full">
                                                    <DropdownMenuItem
                                                        v-for="role in availableRoles"
                                                        :key="role.value"
                                                        @click="inviteRole = role.value"
                                                    >
                                                        {{ role.label }}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                            <input type="hidden" name="role" :value="inviteRole" />
                                            <InputError :message="errors.role" />
                                        </div>
                                    </div>

                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary">
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button type="submit" :disabled="processing">
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
                                    <AvatarImage v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                                    <AvatarFallback>{{ getInitials(member.name) }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium">{{ member.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ member.email }}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <DropdownMenu v-if="member.role !== 'owner' && permissions.canUpdateMember">
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" size="sm">
                                            {{ member.role_label }}
                                            <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem
                                            v-for="role in availableRoles"
                                            :key="role.value"
                                            @click="updateMemberRole(member, role.value)"
                                        >
                                            {{ role.label }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Badge v-else variant="secondary">
                                    {{ member.role_label }}
                                </Badge>

                                <Button
                                    v-if="member.role !== 'owner' && permissions.canRemoveMember"
                                    variant="ghost"
                                    size="sm"
                                    @click="confirmRemoveMember(member)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
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
                            :key="invitation.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                    <Mail class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div>
                                    <div class="font-medium">{{ invitation.email }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ invitation.role_label }}
                                    </div>
                                </div>
                            </div>

                            <Button
                                v-if="permissions.canCancelInvitation"
                                variant="ghost"
                                size="sm"
                                @click="confirmCancelInvitation(invitation)"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div v-if="permissions.canDeleteTeam && !team.is_personal" class="space-y-6">
                    <Heading
                        variant="small"
                        title="Danger Zone"
                        description="Irreversible and destructive actions"
                    />
                    <div
                        class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                    >
                        <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                            <p class="font-medium">Delete this team</p>
                            <p class="text-sm">
                                Once you delete a team, there is no going back. Please be certain.
                            </p>
                        </div>
                        <Dialog v-model:open="deleteDialogOpen">
                            <DialogTrigger as-child>
                                <Button variant="destructive">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete Team
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Are you sure?</DialogTitle>
                                    <DialogDescription>
                                        This action cannot be undone. This will permanently delete the
                                        team <strong>{{ team.name }}</strong> and remove all of its members.
                                    </DialogDescription>
                                </DialogHeader>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button variant="destructive" @click="deleteTeam">
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
                            <strong>{{ memberToRemove?.name }}</strong> from this team?
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">
                                Cancel
                            </Button>
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
                            <strong>{{ invitationToCancel?.email }}</strong>?
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
