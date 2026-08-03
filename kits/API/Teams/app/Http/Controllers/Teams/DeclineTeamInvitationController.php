<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Responses\MessageResponse;
use App\Models\TeamInvitation;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Team Invitations')]
class DeclineTeamInvitationController extends Controller
{
    #[Endpoint(title: 'Decline an invitation', description: 'Decline a pending team invitation for the authenticated user.')]
    public function __invoke(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): MessageResponse
    {
        $invitation->delete();

        return new MessageResponse('Invitation declined successfully.');
    }
}
