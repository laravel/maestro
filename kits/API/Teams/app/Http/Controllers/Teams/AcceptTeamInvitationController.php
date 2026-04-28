<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;

#[Group('Team Invitations')]
class AcceptTeamInvitationController extends Controller
{
    #[Endpoint('Accept an invitation', 'Accept a team invitation using the signed link delivered to the invitee by email. The link must be valid, unexpired, and resolve to an existing user account.')]
    #[ScribeResponse(['message' => 'Invitation accepted successfully.'], description: 'Invitation accepted')]
    #[ScribeResponse(status: Response::HTTP_FORBIDDEN, description: 'Invalid signature or no matching user.')]
    #[ScribeResponse(status: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invitation already accepted or expired.')]
    public function __invoke(Request $request, TeamInvitation $invitation): JsonResponse
    {
        if ($invitation->isAccepted() || $invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer valid.'),
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])
            ->first();

        abort_if($user === null, Response::HTTP_FORBIDDEN);

        DB::transaction(function () use ($user, $invitation) {
            $invitation->team->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);
        });

        return response()->json(['message' => __('Invitation accepted successfully.')]);
    }
}
