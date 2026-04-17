<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Team Members')]
#[Authenticated]
class TeamMemberController extends Controller
{
    #[Endpoint('Update a member role', 'Update the role of a member on the team. Only team owners may update member roles.')]
    #[ResponseFromApiResource(MemberResource::class, User::class)]
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): JsonResponse
    {
        Gate::authorize('updateMember', $team);

        $newRole = TeamRole::from($request->validated('role'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        $member = $team->members()->where('users.id', $user->id)->firstOrFail();

        return (new MemberResource($member))->response($request);
    }

    #[Endpoint('Remove a member', 'Remove a member from the team. The team owner cannot be removed.')]
    #[ScribeResponse(status: Response::HTTP_NO_CONTENT, description: 'No Content')]
    public function destroy(Team $team, User $user): Response
    {
        Gate::authorize('removeMember', $team);

        abort_if($team->owner()?->is($user), Response::HTTP_FORBIDDEN, __('The team owner cannot be removed.'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->delete();

        return response()->noContent();
    }
}
