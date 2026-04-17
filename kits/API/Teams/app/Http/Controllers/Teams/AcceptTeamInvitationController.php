<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
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
    #[Endpoint('Accept an invitation', 'Accept a team invitation as the authenticated user.')]
    #[ScribeResponse(status: Response::HTTP_NO_CONTENT, description: 'No Content')]
    public function __invoke(AcceptTeamInvitationRequest $request, TeamInvitation $invitation): Response
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $invitation) {
            $invitation->team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);
        });

        return response()->noContent();
    }
}
