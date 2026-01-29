<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Events\Teams\TeamDeleted;
use App\Events\Teams\TeamUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display a listing of the user's teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('{{teams_index}}', [
            'teams' => $user->teams()->get()->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_personal' => $team->is_personal,
                'role' => ($role = $user->teamRole($team))?->value,
                'role_label' => $role?->label(),
                'is_current' => $user->current_team_id === $team->id,
            ]),
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function store(SaveTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Show the team edit page.
     */
    public function edit(Request $request, Team $team): Response
    {
        $user = $request->user();

        return Inertia::render('{{teams_edit}}', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'is_personal' => $team->is_personal,
            ],
            'members' => $team->members()->get()->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar' => $member->avatar ?? null,
                'role' => $member->pivot->role->value,
                'role_label' => $member->pivot->role?->label(),
            ]),
            'invitations' => $team->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => [
                'canUpdateTeam' => $user->hasTeamPermission($team, 'team:update'),
                'canDeleteTeam' => $user->hasTeamPermission($team, 'team:delete'),
                'canAddMember' => $user->hasTeamPermission($team, 'member:add'),
                'canUpdateMember' => $user->hasTeamPermission($team, 'member:update'),
                'canRemoveMember' => $user->hasTeamPermission($team, 'member:remove'),
                'canCreateInvitation' => $user->hasTeamPermission($team, 'invitation:create'),
                'canCancelInvitation' => $user->hasTeamPermission($team, 'invitation:cancel'),
            ],
            'availableRoles' => TeamRole::assignable(),
        ]);
    }

    /**
     * Update the specified team.
     */
    public function update(SaveTeamRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team->update(['name' => $request->validated('name')]);
        event(new TeamUpdated($team));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Delete the specified team.
     */
    public function destroy(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $user = $request->user();

        User::where('current_team_id', $team->id)->update(['current_team_id' => null]);
        $team->invitations()->delete();
        $team->memberships()->delete();
        $team->delete();

        event(new TeamDeleted($team));

        $user->refresh();

        if ($user->current_team_id === null) {
            $personalTeam = $user->personalTeam();

            if ($personalTeam) {
                return to_route('dashboard', ['current_team' => $personalTeam->slug]);
            }
        }

        return to_route('teams.index');
    }

    /**
     * Switch the user's current team.
     */
    public function switch(Request $request, Team $team): RedirectResponse
    {
        $request->user()->switchTeam($team);

        return back();
    }
}
