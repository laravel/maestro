<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\RemoveTeamMember;
use App\Actions\Teams\UpdateTeamMember;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    /**
     * Update the specified team member's role.
     */
    public function update(
        UpdateTeamMemberRequest $request,
        Team $team,
        User $user,
        UpdateTeamMember $updateTeamMember,
    ): RedirectResponse {
        $updateTeamMember->handle(
            $request->user(),
            $team,
            $user,
            TeamRole::from($request->validated('role')),
        );

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(
        Request $request,
        Team $team,
        User $user,
        RemoveTeamMember $removeTeamMember,
    ): RedirectResponse {
        $removeTeamMember->handle($request->user(), $team, $user);

        return to_route('teams.edit', ['team' => $team->slug]);
    }
}
