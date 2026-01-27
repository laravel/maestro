<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamMemberAdded;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AddTeamMember
{
    /**
     * Add a member to the team.
     */
    public function handle(User $user, Team $team, User $member, TeamRole $role = TeamRole::Member): Membership
    {
        Gate::forUser($user)->authorize('addMember', $team);

        $membership = $team->memberships()->create([
            'model_type' => $member::class,
            'model_id' => $member->id,
            'role' => $role,
        ]);

        event(new TeamMemberAdded($team, $member, $membership));

        return $membership;
    }
}
