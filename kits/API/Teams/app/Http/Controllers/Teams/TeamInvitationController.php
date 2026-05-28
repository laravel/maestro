<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Http\Resources\TeamInvitationResource;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

#[Group('Team Invitations')]
class TeamInvitationController extends Controller
{
    #[Endpoint(title: 'Invite a user', description: 'Send an invitation email to join the team. Owners and admins may invite members.')]
    public function store(CreateTeamInvitationRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('inviteMember', $team);

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => TeamRole::from($request->validated('role')),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return (new TeamInvitationResource($invitation))
            ->ignoreFieldsAndIncludesInQueryString()
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[Endpoint(title: 'Cancel an invitation', description: 'Cancel a pending invitation for the team.')]
    public function destroy(Team $team, TeamInvitation $invitation): Response
    {
        abort_unless($invitation->team_id === $team->id, Response::HTTP_NOT_FOUND);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        return response()->noContent();
    }
}
