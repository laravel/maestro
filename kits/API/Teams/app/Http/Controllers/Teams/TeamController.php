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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\ResponseFromApiResource;

#[Group('Teams')]
#[Authenticated]
class TeamController extends Controller
{
    #[Endpoint('List teams', 'Return the authenticated user\'s teams as a JSON:API collection.')]
    #[ResponseFromApiResource(TeamResource::class, Team::class, collection: true)]
    public function index(Request $request): JsonResponse
    {
        return TeamResource::collection($request->user()->teams()->get())
            ->response($request);
    }

    #[Endpoint('Create a team', 'Create a new team owned by the authenticated user.')]
    #[ResponseFromApiResource(TeamResource::class, Team::class, status: Response::HTTP_CREATED)]
    public function store(SaveTeamRequest $request, CreateTeam $createTeam): JsonResponse
    {
        $team = $createTeam->handle($request->user(), $request->validated('name'));

        return (new TeamResource($team))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[Endpoint('Show a team', 'Return the team resource with its members, pending invitations, caller permissions, and assignable roles under meta.')]
    #[ResponseFromApiResource(TeamResource::class, Team::class, additional: [
        'meta' => [
            'members' => [
                [
                    'id' => '1',
                    'type' => 'members',
                    'attributes' => [
                        'name' => 'Jane Doe',
                        'email' => 'jane@example.com',
                        'role' => 'owner',
                        'role_label' => 'Owner',
                    ],
                ],
            ],
            'invitations' => [
                [
                    'id' => '1',
                    'type' => 'team_invitations',
                    'attributes' => [
                        'code' => 'abc123',
                        'email' => 'bob@example.com',
                        'role' => 'member',
                        'role_label' => 'Member',
                        'expires_at' => '2026-04-20T10:00:00+00:00',
                        'created_at' => '2026-04-17T10:00:00+00:00',
                    ],
                ],
            ],
            'permissions' => [
                'canUpdateTeam' => true,
                'canDeleteTeam' => true,
                'canAddMember' => true,
                'canUpdateMember' => true,
                'canRemoveMember' => true,
                'canCreateInvitation' => true,
                'canCancelInvitation' => true,
            ],
            'available_roles' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'member', 'label' => 'Member'],
            ],
        ],
    ])]
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
                    'permissions' => $user->toTeamPermissions($team),
                    'available_roles' => TeamRole::assignable(),
                ],
            ])
            ->response($request);
    }

    #[Endpoint('Update a team', 'Update the team name. Only owners may update a team.')]
    #[ResponseFromApiResource(TeamResource::class, Team::class)]
    public function update(SaveTeamRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $team = DB::transaction(function () use ($request, $team) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $request->validated('name')]);

            return $team;
        });

        return (new TeamResource($team))->response($request);
    }

    #[Endpoint('Delete a team', 'Soft delete a team. Requires the team name as confirmation.')]
    #[ScribeResponse(status: Response::HTTP_NO_CONTENT, description: 'No Content')]
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
