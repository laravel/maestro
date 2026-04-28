<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Http\Resources\TeamInvitationResource;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Team Invitations')]
#[Authenticated]
class TeamInvitationController extends Controller
{
    #[Endpoint('Invite a user', 'Send an invitation email to join the team. Owners and admins may invite members.')]
    #[BodyParam('email', 'string', required: true, description: 'The email address of the user to invite.', example: 'jane@example.com')]
    #[BodyParam('role', 'string', required: true, description: 'The role the invited user will have on the team.', example: TeamRole::Member->value)]
    #[ResponseFromApiResource(TeamInvitationResource::class, TeamInvitation::class, status: Response::HTTP_CREATED)]
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
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[Endpoint('Cancel an invitation', 'Cancel a pending invitation for the team.')]
    #[ScribeResponse(status: Response::HTTP_NO_CONTENT, description: 'No Content')]
    public function destroy(Team $team, TeamInvitation $invitation): Response
    {
        abort_unless($invitation->team_id === $team->id, Response::HTTP_NOT_FOUND);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        return response()->noContent();
    }
}
