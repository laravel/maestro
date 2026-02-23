<script lang="ts">
    import { Form, router } from '@inertiajs/svelte';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import Mail from 'lucide-svelte/icons/mail';
    import UserPlus from 'lucide-svelte/icons/user-plus';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogClose,
        DialogContent,
        DialogDescription,
        DialogFooter,
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
    } from '@/components/ui/select';
    import {
        Tooltip,
        TooltipContent,
        TooltipProvider,
        TooltipTrigger,
    } from '@/components/ui/tooltip';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import { getInitials } from '@/lib/initials';
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

    let {
        team,
        members,
        invitations,
        permissions,
        availableRoles,
        isCurrentTeam,
        otherTeams,
    }: {
        team: Team;
        members: TeamMember[];
        invitations: TeamInvitation[];
        permissions: TeamPermissions;
        availableRoles: RoleOption[];
        isCurrentTeam: boolean;
        otherTeams: Team[];
    } = $props();

    const breadcrumbs = $derived<BreadcrumbItem[]>([
        {
            title: 'Teams',
            href: index().url,
        },
        {
            title: team.name,
            href: edit(team.slug).url,
        },
    ]);

    let inviteDialogOpen = $state(false);
    let deleteDialogOpen = $state(false);
    let removeMemberDialogOpen = $state(false);
    let memberToRemove = $state<TeamMember | null>(null);
    let cancelInvitationDialogOpen = $state(false);
    let invitationToCancel = $state<TeamInvitation | null>(null);
    let inviteRole = $state<RoleOption['value']>('member');
    let confirmationName = $state('');
    let newCurrentTeamId = $state('');

    const canDeleteTeam = $derived(
        confirmationName === team.name && (!isCurrentTeam || newCurrentTeamId !== ''),
    );

    const pageTitle = $derived(permissions.canUpdateTeam ? `Edit ${team.name}` : `View ${team.name}`);

    const inviteRoleLabel = $derived(
        availableRoles.find((role) => role.value === inviteRole)?.label ?? 'Select a role',
    );

    const selectedNewCurrentTeam = $derived(
        otherTeams.find((otherTeam) => String(otherTeam.id) === newCurrentTeamId),
    );

    const resetDeleteDialog = () => {
        confirmationName = '';
        newCurrentTeamId = '';
    };

    const updateMemberRole = (member: TeamMember, newRole: string) => {
        router.patch(
            updateMember([team.slug, member.id]).url,
            { role: newRole },
            { preserveScroll: true },
        );
    };

    const confirmRemoveMember = (member: TeamMember) => {
        memberToRemove = member;
        removeMemberDialogOpen = true;
    };

    const removeMember = () => {
        if (!memberToRemove) {
            return;
        }

        router.delete(destroyMember([team.slug, memberToRemove.id]).url, {
            onSuccess: () => {
                removeMemberDialogOpen = false;
                memberToRemove = null;
            },
        });
    };

    const confirmCancelInvitation = (invitation: TeamInvitation) => {
        invitationToCancel = invitation;
        cancelInvitationDialogOpen = true;
    };

    const callClickHandler = (handler: unknown, event: MouseEvent) => {
        if (typeof handler === 'function') {
            handler(event);
        }
    };

    const cancelInvitation = () => {
        if (!invitationToCancel) {
            return;
        }

        router.delete(destroyInvitation([team.slug, invitationToCancel.code]).url, {
            onSuccess: () => {
                cancelInvitationDialogOpen = false;
                invitationToCancel = null;
            },
        });
    };

    const deleteTeam = () => {
        router.delete(destroy(team.slug).url, {
            data: {
                name: confirmationName,
                new_current_team_id: newCurrentTeamId === '' ? null : Number(newCurrentTeamId),
            },
            onSuccess: () => {
                deleteDialogOpen = false;
                resetDeleteDialog();
            },
        });
    };
</script>

<AppHead title={pageTitle} />

<AppLayout {breadcrumbs}>
    <h1 class="sr-only">{pageTitle}</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-10">
            <div class="space-y-6">
                {#if permissions.canUpdateTeam}
                    <Heading
                        variant="small"
                        title="Team Settings"
                        description="Update your team name and settings"
                    />

                    <Form {...update.form(team.slug)} class="space-y-6">
                        {#snippet children({ errors, processing, recentlySuccessful })}
                            <div class="grid gap-2">
                                <Label for="name">Team Name</Label>
                                <Input id="name" name="name" value={team.name} required />
                                <InputError message={errors.name} />
                            </div>

                            <div class="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>Save</Button>

                                {#if recentlySuccessful}
                                    <p class="text-sm text-neutral-600">Saved.</p>
                                {/if}
                            </div>
                        {/snippet}
                    </Form>
                {:else}
                    <Heading variant="small" title={team.name} />
                {/if}
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Team Members"
                        description={permissions.canCreateInvitation
                            ? 'Manage who has access to this team'
                            : ''}
                    />

                    {#if permissions.canCreateInvitation}
                        <Dialog bind:open={inviteDialogOpen}>
                            <DialogTrigger asChild>
                                {#snippet children(props)}
                                    <Button onclick={(event) => callClickHandler(props.onClick, event)}>
                                        <UserPlus class="h-4 w-4" /> Invite Member
                                    </Button>
                                {/snippet}
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    {...storeInvitation.form(team.slug)}
                                    class="space-y-6"
                                    onSuccess={() => (inviteDialogOpen = false)}
                                >
                                    {#snippet children({ errors, processing })}
                                        <div class="space-y-3">
                                            <DialogTitle>Invite a team member</DialogTitle>
                                            <DialogDescription>
                                                Send an invitation to join this team.
                                            </DialogDescription>
                                        </div>

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
                                                <InputError message={errors.email} />
                                            </div>

                                            <div class="grid gap-2">
                                                <Label for="role">Role</Label>
                                                <Select bind:value={inviteRole}>
                                                    <SelectTrigger class="w-full">
                                                        {inviteRoleLabel}
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {#each availableRoles as role (role.value)}
                                                            <SelectItem value={role.value} label={role.label}>
                                                                {role.label}
                                                            </SelectItem>
                                                        {/each}
                                                    </SelectContent>
                                                </Select>

                                                <input type="hidden" name="role" value={inviteRole} />
                                                <InputError message={errors.role} />
                                            </div>
                                        </div>

                                        <DialogFooter class="gap-2">
                                            <DialogClose>
                                                <Button variant="secondary">Cancel</Button>
                                            </DialogClose>

                                            <Button type="submit" disabled={processing}>Send Invitation</Button>
                                        </DialogFooter>
                                    {/snippet}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    {/if}
                </div>

                <div class="space-y-3">
                    {#each members as member (member.id)}
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="flex items-center gap-4">
                                <Avatar class="h-10 w-10">
                                    {#if member.avatar}
                                        <AvatarImage src={member.avatar} alt={member.name} />
                                    {/if}
                                    <AvatarFallback>{getInitials(member.name)}</AvatarFallback>
                                </Avatar>

                                <div>
                                    <div class="font-medium">{member.name}</div>
                                    <div class="text-sm text-muted-foreground">{member.email}</div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {#if member.role !== 'owner' && permissions.canUpdateMember}
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            {#snippet children(props)}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onclick={props.onclick}
                                                    aria-expanded={props['aria-expanded']}
                                                    data-state={props['data-state']}
                                                >
                                                    {member.role_label}
                                                    <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                                </Button>
                                            {/snippet}
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent>
                                            {#each availableRoles as role (role.value)}
                                                <DropdownMenuItem asChild>
                                                    {#snippet children(props)}
                                                        <button
                                                            type="button"
                                                            class={props.class}
                                                            onclick={(event) => {
                                                                props.onClick?.(event);
                                                                updateMemberRole(member, role.value);
                                                            }}
                                                        >
                                                            {role.label}
                                                        </button>
                                                    {/snippet}
                                                </DropdownMenuItem>
                                            {/each}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                {:else}
                                    <Badge variant="secondary">{member.role_label}</Badge>
                                {/if}

                                {#if member.role !== 'owner' && permissions.canRemoveMember}
                                    <TooltipProvider delayDuration={0}>
                                        <Tooltip>
                                            <TooltipTrigger>
                                                {#snippet child({ props })}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        {...props}
                                                        onclick={(event) => {
                                                            callClickHandler(props.onClick, event);
                                                            confirmRemoveMember(member);
                                                        }}
                                                    >
                                                        <X class="h-4 w-4" />
                                                    </Button>
                                                {/snippet}
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Remove member</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                {/if}
                            </div>
                        </div>
                    {/each}
                </div>
            </div>

            {#if invitations.length > 0}
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Pending Invitations"
                        description="Invitations that haven't been accepted yet"
                    />

                    <div class="space-y-3">
                        {#each invitations as invitation (invitation.code)}
                            <div class="flex items-center justify-between rounded-lg border p-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
                                        <Mail class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{invitation.email}</div>
                                        <div class="text-sm text-muted-foreground">{invitation.role_label}</div>
                                    </div>
                                </div>

                                {#if permissions.canCancelInvitation}
                                    <TooltipProvider delayDuration={0}>
                                        <Tooltip>
                                            <TooltipTrigger>
                                                {#snippet child({ props })}
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        {...props}
                                                        onclick={(event) => {
                                                            callClickHandler(props.onClick, event);
                                                            confirmCancelInvitation(invitation);
                                                        }}
                                                    >
                                                        <X class="h-4 w-4" />
                                                    </Button>
                                                {/snippet}
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Cancel invitation</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
            {/if}

            {#if permissions.canDeleteTeam && !team.isPersonal}
                <div class="space-y-6">
                    <Heading
                        variant="small"
                        title="Delete team"
                        description="Delete your team and remove access from all the members to it"
                    />
                    <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                        <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                            <p class="font-medium">Warning</p>
                            <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
                        </div>

                        <Dialog bind:open={deleteDialogOpen}>
                            <DialogTrigger asChild>
                                {#snippet children(props)}
                                    <Button
                                        variant="destructive"
                                        onclick={(event) => callClickHandler(props.onClick, event)}
                                    >
                                        Delete team
                                    </Button>
                                {/snippet}
                            </DialogTrigger>

                            <DialogContent>
                                <div class="space-y-3">
                                    <DialogTitle>Are you sure?</DialogTitle>
                                    <DialogDescription>
                                        This action cannot be undone. This will permanently delete the team
                                        <strong>{team.name}</strong> and remove all of its members.
                                    </DialogDescription>
                                </div>

                                <div class="space-y-4 py-4">
                                    <div class="grid gap-2">
                                        <Label for="confirmation-name">
                                            Type <strong>{team.name}</strong> to confirm
                                        </Label>
                                        <Input
                                            id="confirmation-name"
                                            value={confirmationName}
                                            oninput={(event) =>
                                                (confirmationName = (event.currentTarget as HTMLInputElement).value)}
                                            placeholder="Enter team name"
                                            autocomplete="off"
                                        />
                                    </div>

                                    {#if isCurrentTeam && otherTeams.length > 0}
                                        <div class="grid gap-2">
                                            <Label for="new-current-team">Select a new current team</Label>

                                            <Select bind:value={newCurrentTeamId}>
                                                <SelectTrigger class="w-full">
                                                    {selectedNewCurrentTeam?.name ?? 'Select a team'}
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {#each otherTeams as otherTeam (otherTeam.id)}
                                                        <SelectItem
                                                            value={String(otherTeam.id)}
                                                            label={otherTeam.name}
                                                        >
                                                            {otherTeam.name}
                                                            {#if otherTeam.isPersonal}
                                                                <span class="ml-2 text-muted-foreground">(Personal)</span>
                                                            {/if}
                                                        </SelectItem>
                                                    {/each}
                                                </SelectContent>
                                            </Select>

                                            <p class="text-sm text-muted-foreground">
                                                You are deleting your current team. Please select which team to switch to.
                                            </p>
                                        </div>
                                    {:else if isCurrentTeam && otherTeams.length === 0}
                                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200">
                                            You cannot delete your current team because you have no other teams to switch to. Please create or join another team first.
                                        </div>
                                    {/if}
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose>
                                        <Button variant="secondary" onclick={resetDeleteDialog}>Cancel</Button>
                                    </DialogClose>

                                    <Button variant="destructive" disabled={!canDeleteTeam} onclick={deleteTeam}>
                                        Delete Team
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            {/if}
        </div>

        <Dialog bind:open={removeMemberDialogOpen}>
            <DialogContent>
                <div class="space-y-3">
                    <DialogTitle>Remove team member</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove <strong>{memberToRemove?.name}</strong> from this team?
                    </DialogDescription>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button variant="destructive" onclick={removeMember}>Remove Member</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog bind:open={cancelInvitationDialogOpen}>
            <DialogContent>
                <div class="space-y-3">
                    <DialogTitle>Cancel invitation</DialogTitle>
                    <DialogDescription class="mb-3">
                        Are you sure you want to cancel the invitation for <strong>{invitationToCancel?.email}</strong>?
                    </DialogDescription>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose>
                        <Button variant="secondary">Keep Invitation</Button>
                    </DialogClose>

                    <Button variant="destructive" onclick={cancelInvitation}>Cancel Invitation</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </SettingsLayout>
</AppLayout>
