<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamMemberRemoved;
use App\Events\Teams\TeamMemberRoleChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Teams\RemovedFromTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $newRole = TeamRole::from($request->validated('role'));

        $membership = $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $oldRole = $membership->role;
        $membership->update(['role' => $newRole]);

        event(new TeamMemberRoleChanged($team, $user, $oldRole, $newRole));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        $team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentTeam($team)) {
            $user->switchTeam($user->personalTeam());
        }

        event(new TeamMemberRemoved($team, $user));

        $user->notify(new RemovedFromTeam($team));

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
