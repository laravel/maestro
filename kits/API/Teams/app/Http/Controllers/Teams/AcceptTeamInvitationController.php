<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\AcceptTeamInvitationRequest;
use App\Http\Responses\MessageResponse;
use App\Models\TeamInvitation;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\DB;

#[Group('Team Invitations')]
class AcceptTeamInvitationController extends Controller
{
    #[Endpoint(title: 'Accept an invitation', description: 'Accept a pending team invitation for the authenticated user.')]
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
