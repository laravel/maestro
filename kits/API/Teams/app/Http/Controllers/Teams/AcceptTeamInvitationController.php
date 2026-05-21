<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Responses\MessageResponse;
use App\Models\TeamInvitation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Team Invitations')]
#[Authenticated]
class AcceptTeamInvitationController extends Controller
{
    #[Endpoint('Accept an invitation', 'Accept a pending team invitation for the authenticated user.')]
    #[ScribeResponse(['message' => 'Invitation accepted successfully.'], description: 'Invitation accepted')]
    #[ScribeResponse(status: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invitation already accepted, expired, or sent to a different email address.')]
    public function __invoke(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): MessageResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $invitation) {
            $invitation->team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);
        });

        return new MessageResponse('Invitation accepted successfully.');
    }
}
