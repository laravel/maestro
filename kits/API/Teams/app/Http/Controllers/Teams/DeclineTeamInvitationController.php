<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Responses\MessageResponse;
use App\Models\TeamInvitation;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Team Invitations')]
#[Authenticated]
class DeclineTeamInvitationController extends Controller
{
    #[Endpoint('Decline an invitation', 'Decline a pending team invitation for the authenticated user.')]
    #[ScribeResponse(['message' => 'Invitation declined successfully.'], description: 'Invitation declined')]
    #[ScribeResponse(status: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invitation already accepted, expired, or sent to a different email address.')]
    public function __invoke(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): MessageResponse
    {
        $invitation->delete();

        return new MessageResponse('Invitation declined successfully.');
    }
}
