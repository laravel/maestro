<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\DeleteTeamRequest;
use App\Http\Requests\Teams\SaveTeamRequest;
use App\Http\Resources\MemberResource;
use App\Http\Resources\TeamInvitationResource;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

#[Group('Teams')]
class TeamController extends Controller
{
    #[Endpoint(title: 'List teams', description: 'Return the authenticated user\'s teams as a JSON:API collection.')]
    public function index(Request $request): JsonResponse
    {
        return TeamResource::collection($request->user()->teams()->get())
            ->response($request);
    }

    #[Endpoint(title: 'Create a team', description: 'Create a new team owned by the authenticated user.')]
    public function store(SaveTeamRequest $request, CreateTeam $createTeam): JsonResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));

        return (new TeamResource($team))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[Endpoint(title: 'Show a team', description: 'Return the team resource with its members, pending invitations, caller permissions, and assignable roles under meta.')]
    public function show(Request $request, Team $team): JsonResponse
    {
        $user = $request->user();

        return (new TeamResource($team))
            ->additional([
                'meta' => [
                    'members' => MemberResource::collection($team->members()->get()),
                    'invitations' => TeamInvitationResource::collection(
                        $team->invitations()->whereNull('accepted_at')->get(),
                    ),
                    'permissions' => $user->toTeamPermissions($team)->toArray(),
                    'available_roles' => TeamRole::assignable(),
                ],
            ])
            ->response($request);
    }

    #[Endpoint(title: 'Update a team', description: 'Update the team name. Only owners may update a team.')]
    public function update(SaveTeamRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $team = DB::transaction(function () use ($request, $team) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $request->validated('name')]);

            return $team;
        });

        return (new TeamResource($team))
            ->response($request);
    }

    #[Endpoint(title: 'Delete a team', description: 'Soft delete a team. Requires the team name as confirmation.')]
    public function destroy(DeleteTeamRequest $request, Team $team): Response
    {
        DB::transaction(function () use ($team) {
            $team->invitations()->delete();
            $team->memberships()->delete();
            $team->delete();
        });

        return response()->noContent();
    }
}
