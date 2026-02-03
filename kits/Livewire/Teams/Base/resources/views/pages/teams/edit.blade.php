<?php

use App\Enums\TeamRole;
use App\Events\Teams\TeamDeleted;
use App\Events\Teams\TeamInvitationCancelled;
use App\Events\Teams\TeamInvitationSent;
use App\Events\Teams\TeamMemberRemoved;
use App\Events\Teams\TeamMemberRoleChanged;
use App\Events\Teams\TeamUpdated;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Teams\RemovedFromTeam;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\TeamName;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Team $teamModel;

    public array $teamData = [];

    public array $members = [];

    public array $invitations = [];

    public array $permissions = [];

    public array $availableRoles = [];

    public bool $isCurrentTeam = false;

    public array $otherTeams = [];

    public string $teamName = '';

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    public string $deleteName = '';

    public string|int|null $newCurrentTeamId = null;

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
        $this->teamName = $team->name;

        $this->setTeamData();
    }

    private function setTeamData(): void
    {
        $user = Auth::user();
        $team = $this->teamModel->fresh();

        $this->teamData = [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_personal' => $team->is_personal,
        ];

        $this->members = $team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role?->label(),
        ])->toArray();

        $this->invitations = $team->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'created_at' => $invitation->created_at->toISOString(),
            ])->toArray();

        $this->permissions = [
            'canUpdateTeam' => $user->hasTeamPermission($team, 'team:update'),
            'canDeleteTeam' => $user->hasTeamPermission($team, 'team:delete'),
            'canAddMember' => $user->hasTeamPermission($team, 'member:add'),
            'canUpdateMember' => $user->hasTeamPermission($team, 'member:update'),
            'canRemoveMember' => $user->hasTeamPermission($team, 'member:remove'),
            'canCreateInvitation' => $user->hasTeamPermission($team, 'invitation:create'),
            'canCancelInvitation' => $user->hasTeamPermission($team, 'invitation:cancel'),
        ];

        $this->availableRoles = TeamRole::assignable();
        $this->isCurrentTeam = $user->isCurrentTeam($team);
        $this->otherTeams = $user->teams()
            ->where('teams.id', '!=', $team->id)
            ->get()
            ->map(fn (Team $otherTeam) => [
                'id' => $otherTeam->id,
                'name' => $otherTeam->name,
                'slug' => $otherTeam->slug,
                'is_personal' => $otherTeam->is_personal,
            ])->toArray();
    }

    public function updateTeam(): void
    {
        Gate::authorize('update', $this->teamModel);

        $validated = $this->validate([
            'teamName' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $this->teamModel->update(['name' => $validated['teamName']]);
        event(new TeamUpdated($this->teamModel));
        $this->setTeamData();

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->fresh()->slug], navigate: true);
    }

    public function updateMember(int $userId, string $role): void
    {
        Gate::authorize('updateMember', $this->teamModel);

        $validated = Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::enum(TeamRole::class)],
        ])->validate();

        $newRole = TeamRole::from($validated['role']);

        $membership = $this->teamModel->memberships()
            ->where('user_id', $userId)
            ->firstOrFail();

        $oldRole = $membership->role;
        $membership->update(['role' => $newRole]);

        event(new TeamMemberRoleChanged($this->teamModel, $membership->user, $oldRole, $newRole));
        $this->setTeamData();

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function removeMember(int $userId): void
    {
        Gate::authorize('removeMember', $this->teamModel);

        $user = User::findOrFail($userId);

        $this->teamModel->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentTeam($this->teamModel)) {
            $user->switchTeam($user->personalTeam());
        }

        event(new TeamMemberRemoved($this->teamModel, $user));
        $user->notify(new RemovedFromTeam($this->teamModel));
        $this->setTeamData();

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function createInvitation(): void
    {
        Gate::authorize('inviteMember', $this->teamModel);

        $validated = $this->validate([
            'inviteEmail' => ['required', 'string', 'email', 'max:255'],
            'inviteRole' => ['required', 'string', Rule::enum(TeamRole::class)],
        ]);

        $expiryMinutes = config('teams.invitations.default_expiry');

        $invitation = $this->teamModel->invitations()->create([
            'email' => $validated['inviteEmail'],
            'role' => TeamRole::from($validated['inviteRole']),
            'invited_by' => Auth::id(),
            'expires_at' => $expiryMinutes ? now()->addMinutes($expiryMinutes) : null,
        ]);

        event(new TeamInvitationSent($invitation));
        Notification::route('mail', $invitation->email)->notify(new TeamInvitationNotification($invitation));

        $this->reset('inviteEmail', 'inviteRole');
        $this->setTeamData();

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function cancelInvitation(string $code): void
    {
        $invitation = $this->teamModel->invitations()->where('code', $code)->firstOrFail();

        Gate::authorize('cancelInvitation', $this->teamModel);

        $invitation->delete();
        event(new TeamInvitationCancelled($invitation));
        $this->setTeamData();

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function deleteTeam(): void
    {
        Gate::authorize('delete', $this->teamModel);

        $validated = $this->validate([
            'deleteName' => ['required', 'string'],
            'newCurrentTeamId' => ['nullable', 'numeric', 'exists:teams,id'],
        ]);

        if ($validated['deleteName'] !== $this->teamModel->name) {
            $this->addError('deleteName', 'The team name does not match.');

            return;
        }

        $user = Auth::user();

        $isDeletingCurrentTeam = $user->isCurrentTeam($this->teamModel);
        $newTeamId = $validated['newCurrentTeamId'] ?? null;
        $newTeamId = $newTeamId !== '' && $newTeamId !== null ? (int) $newTeamId : null;

        if ($isDeletingCurrentTeam && ! $newTeamId) {
            $this->addError('newCurrentTeamId', 'You must select a new current team.');

            return;
        }

        if ($newTeamId) {
            $belongsToTeam = $user->teams()->where('teams.id', $newTeamId)->exists();

            if (! $belongsToTeam) {
                $this->addError('newCurrentTeamId', 'You do not belong to this team.');

                return;
            }
        }

        User::where('current_team_id', $this->teamModel->id)
            ->where('id', '!=', $user->id)
            ->each(fn (User $affectedUser) => $affectedUser->switchTeam($affectedUser->personalTeam()));

        $this->teamModel->invitations()->delete();
        $this->teamModel->memberships()->delete();
        $this->teamModel->delete();

        event(new TeamDeleted($this->teamModel));

        if ($newTeamId) {
            $user->switchTeam(Team::findOrFail($newTeamId));
        }

        $this->redirectRoute('teams.index', navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

        <flux:heading class="sr-only">{{ __('Teams') }}</flux:heading>

        <x-pages::settings.layout :heading="__('Teams')" :subheading="__('Manage your team settings')">
            <div class="space-y-10">
            <div class="space-y-6">
                @if ($permissions['canUpdateTeam'])
                    <div class="space-y-4">
                        <form wire:submit="updateTeam" class="space-y-6">
                            <flux:input wire:model="teamName" :label="__('Team name')" required />

                            <flux:button variant="primary" type="submit">
                                {{ __('Save') }}
                            </flux:button>
                        </form>
                    </div>
                @else
                    <div>
                        <flux:heading>{{ __('Team Name') }}</flux:heading>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $teamData['name'] }}</flux:text>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading>{{ __('Team Members') }}</flux:heading>
                        @if ($permissions['canAddMember'] || $permissions['canUpdateMember'] || $permissions['canRemoveMember'])
                            <flux:subheading>{{ __('Manage who has access to this team') }}</flux:subheading>
                        @endif
                    </div>

                    @if ($permissions['canCreateInvitation'])
                        <flux:modal.trigger name="invite-member">
                            <flux:button variant="primary" icon="user-plus" x-data="" x-on:click.prevent="$dispatch('open-modal', 'invite-member')">
                                {{ __('Invite Member') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                <div class="space-y-3">
                    @foreach ($members as $member)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-center gap-4">
                                <flux:avatar :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                                <div>
                                    <div class="font-medium">{{ $member['name'] }}</div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($member['role'] !== 'owner' && $permissions['canUpdateMember'])
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="outline" size="sm" icon:trailing="chevron-down">
                                            {{ $member['role_label'] }}
                                        </flux:button>
                                        <flux:menu>
                                            @foreach ($availableRoles as $role)
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    wire:click="updateMember({{ $member['id'] }}, '{{ $role['value'] }}')"
                                                >
                                                    {{ $role['label'] }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                @else
                                    <flux:badge color="zinc">{{ $member['role_label'] }}</flux:badge>
                                @endif

                                @if ($member['role'] !== 'owner' && $permissions['canRemoveMember'])
                                    <flux:modal.trigger name="remove-member-{{ $member['id'] }}">
                                        <flux:tooltip :content="__('Remove member')">
                                            <flux:button variant="ghost" size="sm" icon="x-mark" x-data="" x-on:click.prevent="$dispatch('open-modal', 'remove-member-{{ $member['id'] }}')" />
                                        </flux:tooltip>
                                    </flux:modal.trigger>
                                @endif
                            </div>
                        </div>

                        @if ($member['role'] !== 'owner' && $permissions['canRemoveMember'])
                            <flux:modal name="remove-member-{{ $member['id'] }}" focusable class="max-w-lg">
                                <form wire:submit="removeMember({{ $member['id'] }})" class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Remove team member') }}</flux:heading>
                                        <flux:subheading>
                                            {{ __('Are you sure you want to remove :name from this team?', ['name' => $member['name']]) }}
                                        </flux:subheading>
                                    </div>
                                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button variant="danger" type="submit">{{ __('Remove Member') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endif
                    @endforeach
                </div>
            </div>

            @if (count($invitations) > 0)
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Pending Invitations') }}</flux:heading>
                        <flux:subheading>{{ __('Invitations that have not been accepted yet') }}</flux:subheading>
                    </div>

                    <div class="space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon name="envelope" class="text-zinc-500" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $invitation['email'] }}</div>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $invitation['role_label'] }}</flux:text>
                                    </div>
                                </div>

                                @if ($permissions['canCancelInvitation'])
                                    <flux:modal.trigger name="cancel-invitation-{{ $invitation['code'] }}">
                                        <flux:tooltip :content="__('Cancel invitation')">
                                            <flux:button variant="ghost" size="sm" icon="x-mark" x-data="" x-on:click.prevent="$dispatch('open-modal', 'cancel-invitation-{{ $invitation['code'] }}')" />
                                        </flux:tooltip>
                                    </flux:modal.trigger>
                                @endif
                            </div>

                            @if ($permissions['canCancelInvitation'])
                                <flux:modal name="cancel-invitation-{{ $invitation['code'] }}" focusable class="max-w-lg">
                                    <form wire:submit="cancelInvitation('{{ $invitation['code'] }}')" class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">{{ __('Cancel invitation') }}</flux:heading>
                                            <flux:subheading>
                                                {{ __('Are you sure you want to cancel the invitation for :email?', ['email' => $invitation['email']]) }}
                                            </flux:subheading>
                                        </div>
                                        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                            <flux:modal.close>
                                                <flux:button variant="filled">{{ __('Keep Invitation') }}</flux:button>
                                            </flux:modal.close>
                                            <flux:button variant="danger" type="submit">{{ __('Cancel Invitation') }}</flux:button>
                                        </div>
                                    </form>
                                </flux:modal>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($permissions['canDeleteTeam'] && ! $teamData['is_personal'])
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Danger Zone') }}</flux:heading>
                        <flux:subheading>{{ __('Irreversible and destructive actions') }}</flux:subheading>
                    </div>

                    <div class="space-y-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-200/10 dark:bg-red-900/20 dark:text-red-100">
                        <div>
                            <p class="font-medium">{{ __('Delete this team') }}</p>
                            <p class="text-sm">{{ __('Once you delete a team, there is no going back. Please be certain.') }}</p>
                        </div>

                        <flux:modal.trigger name="delete-team">
                            <flux:button variant="danger" x-data="" x-on:click.prevent="$wire.set('deleteName', ''); $wire.set('newCurrentTeamId', null); $dispatch('open-modal', 'delete-team')">
                                {{ __('Delete Team') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            @endif
        </div>
        </x-pages::settings.layout>

    @if ($permissions['canCreateInvitation'])
        <flux:modal name="invite-member" focusable class="max-w-lg">
            <form wire:submit="createInvitation" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Invite a team member') }}</flux:heading>
                    <flux:subheading>{{ __('Send an invitation to join this team.') }}</flux:subheading>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="inviteEmail" type="email" :label="__('Email address')" required />

                    <flux:select wire:model="inviteRole" :label="__('Role')">
                        @foreach ($availableRoles as $role)
                            <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Send Invitation') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($permissions['canDeleteTeam'] && ! $teamData['is_personal'])
        <flux:modal name="delete-team" focusable class="max-w-lg">
            <form wire:submit="deleteTeam" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Are you sure?') }}</flux:heading>
                    <flux:subheading>
                        {{ __('This action cannot be undone. This will permanently delete the team :name and remove all of its members.', ['name' => $teamData['name']]) }}
                    </flux:subheading>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="deleteName" :label="__('Type :name to confirm', ['name' => $teamData['name']])" required />

                    @if ($isCurrentTeam && count($otherTeams) > 0)
                        <div class="space-y-2">
                            <flux:select wire:model.live="newCurrentTeamId" :label="__('Select a new current team')">
                                <flux:select.option value="">{{ __('Select a team') }}</flux:select.option>
                                @foreach ($otherTeams as $otherTeam)
                                    <flux:select.option value="{{ $otherTeam['id'] }}">{{ $otherTeam['name'] }}@if ($otherTeam['is_personal']) ({{ __('Personal') }})@endif</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('You are deleting your current team. Please select which team to switch to.') }}
                            </flux:text>
                        </div>
                    @elseif ($isCurrentTeam && count($otherTeams) === 0)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200">
                            {{ __('You cannot delete your current team because you have no other teams to switch to. Please create or join another team first.') }}
                        </div>
                    @endif
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" type="submit">
                        {{ __('Delete Team') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</section>
