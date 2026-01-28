<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\AcceptInvitation;
use App\Actions\Teams\CancelInvitation;
use App\Actions\Teams\InviteTeamMember;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(
        CreateTeamInvitationRequest $request,
        Team $team,
        InviteTeamMember $inviteTeamMember,
    ): RedirectResponse {
        $inviteTeamMember->handle(
            $request->user(),
            $team,
            $request->validated('email'),
            TeamRole::from($request->validated('role')),
        );

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(
        Request $request,
        Team $team,
        TeamInvitation $invitation,
        CancelInvitation $cancelInvitation,
    ): RedirectResponse {
        $cancelInvitation->handle($request->user(), $invitation);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(
        Request $request,
        TeamInvitation $invitation,
        AcceptInvitation $acceptInvitation,
    ): RedirectResponse {
        $acceptInvitation->handle($request->user(), $invitation);

        return to_route('dashboard', ['current_team' => $invitation->team->slug]);
    }
}
