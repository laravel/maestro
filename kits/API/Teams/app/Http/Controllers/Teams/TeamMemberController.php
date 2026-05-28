<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Team;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Team Members')]
class TeamMemberController extends Controller
{
    #[Endpoint(title: 'Update a member role', description: 'Update the role of a member on the team. Only team owners may update member roles.')]
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): JsonResponse
    {
        Gate::authorize('updateMember', $team);

        abort_if($request->user()->is($user), Response::HTTP_FORBIDDEN, __('You cannot change your own role.'));

        $newRole = TeamRole::from($request->validated('role'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        $member = $team->members()->where('users.id', $user->id)->firstOrFail();

        return (new MemberResource($member))
            ->response($request);
    }

    #[Endpoint(title: 'Remove a member', description: 'Remove a member from the team. The team owner cannot be removed.')]
    public function destroy(Team $team, User $user): Response
    {
        Gate::authorize('removeMember', $team);

        abort_if($team->owner()?->is($user), Response::HTTP_FORBIDDEN, __('The team owner cannot be removed.'));

        $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->delete();

        return response()->noContent();
    }
}
