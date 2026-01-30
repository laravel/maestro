import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type {
    BreadcrumbItem,
    RoleOption,
    Team,
    TeamInvitation,
    TeamMember,
    TeamOption,
    TeamPermissions,
} from '@/types';
import { Form, Head, router } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import { ChevronDown, Mail, Trash2, UserPlus, X } from 'lucide-react';
import { useMemo, useState } from 'react';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
    isCurrentTeam: boolean;
    otherTeams: TeamOption[];
};

export default function TeamEdit({
    team,
    members,
    invitations,
    permissions,
    availableRoles,
    isCurrentTeam,
    otherTeams,
}: Props) {
    const getInitials = useInitials();
    const breadcrumbs = useMemo<BreadcrumbItem[]>(
        () => [
            {
                title: 'Teams',
                href: '/teams',
            },
            {
                title: team.name,
                href: `/teams/${team.slug}`,
            },
        ],
        [team.name, team.slug],
    );

    const [inviteDialogOpen, setInviteDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [removeMemberDialogOpen, setRemoveMemberDialogOpen] =
        useState(false);
    const [memberToRemove, setMemberToRemove] =
        useState<TeamMember | null>(null);
    const [cancelInvitationDialogOpen, setCancelInvitationDialogOpen] =
        useState(false);
    const [invitationToCancel, setInvitationToCancel] =
        useState<TeamInvitation | null>(null);
    const [inviteRole, setInviteRole] = useState<RoleOption['value']>('member');
    const [confirmationName, setConfirmationName] = useState('');
    const [newCurrentTeamId, setNewCurrentTeamId] = useState<number | null>(
        null,
    );

    const canDeleteTeam = useMemo(() => {
        const nameMatches = confirmationName === team.name;
        const hasNewTeamIfNeeded = !isCurrentTeam || newCurrentTeamId !== null;

        return nameMatches && hasNewTeamIfNeeded;
    }, [confirmationName, isCurrentTeam, newCurrentTeamId, team.name]);

    const resetDeleteDialog = () => {
        setConfirmationName('');
        setNewCurrentTeamId(null);
    };

    const inviteRoleLabel = useMemo(() => {
        const role = availableRoles.find((item) => item.value === inviteRole);
        return role?.label ?? inviteRole;
    }, [availableRoles, inviteRole]);

    const updateMemberRole = (member: TeamMember, newRole: string) => {
        router.patch(`/teams/${team.slug}/members/${member.id}`, {
            role: newRole,
        });
    };

    const confirmRemoveMember = (member: TeamMember) => {
        setMemberToRemove(member);
        setRemoveMemberDialogOpen(true);
    };

    const removeMember = () => {
        if (!memberToRemove) {
            return;
        }

        router.delete(`/teams/${team.slug}/members/${memberToRemove.id}`, {
            onSuccess: () => {
                setRemoveMemberDialogOpen(false);
                setMemberToRemove(null);
            },
        });
    };

    const confirmCancelInvitation = (invitation: TeamInvitation) => {
        setInvitationToCancel(invitation);
        setCancelInvitationDialogOpen(true);
    };

    const cancelInvitation = () => {
        if (!invitationToCancel) {
            return;
        }

        router.delete(
            `/teams/${team.slug}/invitations/${invitationToCancel.code}`,
            {
                onSuccess: () => {
                    setCancelInvitationDialogOpen(false);
                    setInvitationToCancel(null);
                },
            },
        );
    };

    const deleteTeam = () => {
        router.delete(`/teams/${team.slug}`, {
            data: {
                name: confirmationName,
                new_current_team_id: newCurrentTeamId,
            },
            onSuccess: () => {
                setDeleteDialogOpen(false);
                resetDeleteDialog();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${team.name}`} />

            <h1 className="sr-only">Edit Team: {team.name}</h1>

            <SettingsLayout>
                <div className="flex flex-col space-y-10">
                    <div className="space-y-6">
                        {permissions.canUpdateTeam ? (
                            <>
                                <Heading
                                    variant="small"
                                    title="Team Settings"
                                    description="Update your team name and settings"
                                />

                                <Form
                                    action={`/teams/${team.slug}`}
                                    method="patch"
                                    className="space-y-6"
                                >
                                    {({ errors, processing, recentlySuccessful }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="name">
                                                    Team Name
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    defaultValue={team.name}
                                                    required
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className="flex items-center gap-4">
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    Save
                                                </Button>

                                                <Transition
                                                    show={recentlySuccessful}
                                                    enter="transition ease-in-out"
                                                    enterFrom="opacity-0"
                                                    leave="transition ease-in-out"
                                                    leaveTo="opacity-0"
                                                >
                                                    <p className="text-sm text-neutral-600">
                                                        Saved.
                                                    </p>
                                                </Transition>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            </>
                        ) : (
                            <>
                                <Heading
                                    variant="small"
                                    title="Team Name"
                                />
                                <p className="text-foreground">{team.name}</p>
                            </>
                        )}
                    </div>

                    <div className="space-y-6">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Team Members"
                                description="Manage who has access to this team"
                            />

                            {permissions.canCreateInvitation ? (
                                <Dialog
                                    open={inviteDialogOpen}
                                    onOpenChange={setInviteDialogOpen}
                                >
                                    <DialogTrigger asChild>
                                        <Button>
                                            <UserPlus className="mr-2 h-4 w-4" />
                                            Invite Member
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <Form
                                            action={`/teams/${team.slug}/invitations`}
                                            method="post"
                                            className="space-y-6"
                                            onSuccess={() =>
                                                setInviteDialogOpen(false)
                                            }
                                        >
                                            {({ errors, processing }) => (
                                                <>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Invite a team member
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Send an invitation to
                                                            join this team.
                                                        </DialogDescription>
                                                    </DialogHeader>

                                                    <div className="grid gap-4">
                                                        <div className="grid gap-2">
                                                            <Label htmlFor="email">
                                                                Email Address
                                                            </Label>
                                                            <Input
                                                                id="email"
                                                                name="email"
                                                                type="email"
                                                                placeholder="colleague@example.com"
                                                                required
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.email
                                                                }
                                                            />
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label htmlFor="role">
                                                                Role
                                                            </Label>
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger
                                                                    asChild
                                                                >
                                                                    <Button
                                                                        variant="outline"
                                                                        className="w-full justify-between"
                                                                    >
                                                                        {
                                                                            inviteRoleLabel
                                                                        }
                                                                        <ChevronDown className="ml-2 h-4 w-4 opacity-50" />
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent className="w-full">
                                                                    {availableRoles.map(
                                                                        (role) => (
                                                                            <DropdownMenuItem
                                                                                key={
                                                                                    role.value
                                                                                }
                                                                                onSelect={() =>
                                                                                    setInviteRole(
                                                                                        role.value,
                                                                                    )
                                                                                }
                                                                            >
                                                                                {role.label}
                                                                            </DropdownMenuItem>
                                                                        ),
                                                                    )}
                                                                </DropdownMenuContent>
                                                            </DropdownMenu>
                                                            <input
                                                                type="hidden"
                                                                name="role"
                                                                value={inviteRole}
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors.role
                                                                }
                                                            />
                                                        </div>
                                                    </div>

                                                    <DialogFooter className="gap-2">
                                                        <DialogClose asChild>
                                                            <Button variant="secondary">
                                                                Cancel
                                                            </Button>
                                                        </DialogClose>

                                                        <Button
                                                            type="submit"
                                                            disabled={processing}
                                                        >
                                                            Send Invitation
                                                        </Button>
                                                    </DialogFooter>
                                                </>
                                            )}
                                        </Form>
                                    </DialogContent>
                                </Dialog>
                            ) : null}
                        </div>

                        <div className="space-y-3">
                            {members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex items-center justify-between rounded-lg border p-4"
                                >
                                    <div className="flex items-center gap-4">
                                        <Avatar className="h-10 w-10">
                                            {member.avatar ? (
                                                <AvatarImage
                                                    src={member.avatar}
                                                    alt={member.name}
                                                />
                                            ) : null}
                                            <AvatarFallback>
                                                {getInitials(member.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div className="font-medium">
                                                {member.name}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {member.email}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        {member.role !== 'owner' &&
                                        permissions.canUpdateMember ? (
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        {member.role_label}
                                                        <ChevronDown className="ml-2 h-4 w-4 opacity-50" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent>
                                                    {availableRoles.map((role) => (
                                                        <DropdownMenuItem
                                                            key={role.value}
                                                            onSelect={() =>
                                                                updateMemberRole(
                                                                    member,
                                                                    role.value,
                                                                )
                                                            }
                                                        >
                                                            {role.label}
                                                        </DropdownMenuItem>
                                                    ))}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        ) : (
                                            <Badge variant="secondary">
                                                {member.role_label}
                                            </Badge>
                                        )}

                                        {member.role !== 'owner' &&
                                        permissions.canRemoveMember ? (
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                confirmRemoveMember(
                                                                    member,
                                                                )
                                                            }
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Remove member</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        ) : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {invitations.length > 0 ? (
                        <div className="space-y-6">
                            <Heading
                                variant="small"
                                title="Pending Invitations"
                                description="Invitations that haven't been accepted yet"
                            />

                            <div className="space-y-3">
                                {invitations.map((invitation) => (
                                    <div
                                        key={invitation.code}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                                <Mail className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <div className="font-medium">
                                                    {invitation.email}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {invitation.role_label}
                                                </div>
                                            </div>
                                        </div>

                                        {permissions.canCancelInvitation ? (
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                confirmCancelInvitation(
                                                                    invitation,
                                                                )
                                                            }
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Cancel invitation</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {permissions.canDeleteTeam && !team.is_personal ? (
                        <div className="space-y-6">
                            <Heading
                                variant="small"
                                title="Danger Zone"
                                description="Irreversible and destructive actions"
                            />
                            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                    <p className="font-medium">
                                        Delete this team
                                    </p>
                                    <p className="text-sm">
                                        Once you delete a team, there is no
                                        going back. Please be certain.
                                    </p>
                                </div>
                                <Dialog
                                    open={deleteDialogOpen}
                                    onOpenChange={(open) => {
                                        setDeleteDialogOpen(open);
                                        if (!open) {
                                            resetDeleteDialog();
                                        }
                                    }}
                                >
                                    <DialogTrigger asChild>
                                        <Button variant="destructive">
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Delete Team
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Are you sure?
                                            </DialogTitle>
                                            <DialogDescription>
                                                This action cannot be undone.
                                                This will permanently delete the
                                                team <strong>{team.name}</strong>{' '}
                                                and remove all of its members.
                                            </DialogDescription>
                                        </DialogHeader>

                                        <div className="space-y-4 py-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="confirmation-name">
                                                    Type{' '}
                                                    <strong>{team.name}</strong>{' '}
                                                    to confirm
                                                </Label>
                                                <Input
                                                    id="confirmation-name"
                                                    value={confirmationName}
                                                    onChange={(event) =>
                                                        setConfirmationName(
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="Enter team name"
                                                    autoComplete="off"
                                                />
                                            </div>

                                            {isCurrentTeam &&
                                            otherTeams.length > 0 ? (
                                                <div className="grid gap-2">
                                                    <Label htmlFor="new-current-team">
                                                        Select a new current team
                                                    </Label>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger
                                                            asChild
                                                        >
                                                            <Button
                                                                variant="outline"
                                                                className="w-full justify-between"
                                                            >
                                                                {otherTeams.find(
                                                                    (otherTeam) =>
                                                                        otherTeam.id ===
                                                                        newCurrentTeamId,
                                                                )?.name ??
                                                                    'Select a team'}
                                                                <ChevronDown className="ml-2 h-4 w-4 opacity-50" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent className="w-full">
                                                            {otherTeams.map(
                                                                (otherTeam) => (
                                                                    <DropdownMenuItem
                                                                        key={
                                                                            otherTeam.id
                                                                        }
                                                                        onSelect={() =>
                                                                            setNewCurrentTeamId(
                                                                                otherTeam.id,
                                                                            )
                                                                        }
                                                                    >
                                                                        {
                                                                            otherTeam.name
                                                                        }
                                                                        {otherTeam.is_personal ? (
                                                                            <span className="ml-2 text-muted-foreground">
                                                                                (Personal)
                                                                            </span>
                                                                        ) : null}
                                                                    </DropdownMenuItem>
                                                                ),
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                    <p className="text-sm text-muted-foreground">
                                                        You are deleting your
                                                        current team. Please
                                                        select which team to
                                                        switch to.
                                                    </p>
                                                </div>
                                            ) : null}

                                            {isCurrentTeam &&
                                            otherTeams.length === 0 ? (
                                                <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200">
                                                    You cannot delete your
                                                    current team because you have
                                                    no other teams to switch to.
                                                    Please create or join another
                                                    team first.
                                                </div>
                                            ) : null}
                                        </div>

                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Cancel
                                                </Button>
                                            </DialogClose>

                                            <Button
                                                variant="destructive"
                                                disabled={!canDeleteTeam}
                                                onClick={deleteTeam}
                                            >
                                                Delete Team
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>
                    ) : null}
                </div>

                <Dialog
                    open={removeMemberDialogOpen}
                    onOpenChange={setRemoveMemberDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Remove team member</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to remove{' '}
                                <strong>{memberToRemove?.name}</strong> from
                                this team?
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>

                            <Button variant="destructive" onClick={removeMember}>
                                Remove Member
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog
                    open={cancelInvitationDialogOpen}
                    onOpenChange={setCancelInvitationDialogOpen}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Cancel invitation</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to cancel the invitation
                                for{' '}
                                <strong>{invitationToCancel?.email}</strong>?
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">
                                    Keep Invitation
                                </Button>
                            </DialogClose>

                            <Button
                                variant="destructive"
                                onClick={cancelInvitation}
                            >
                                Cancel Invitation
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </SettingsLayout>
        </AppLayout>
    );
}
