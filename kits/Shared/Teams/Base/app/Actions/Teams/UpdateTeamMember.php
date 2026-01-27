<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamMemberRoleChanged;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateTeamMember
{
    /**
     * Update a team member's role.
     */
    public function handle(User $user, Team $team, User $member, TeamRole $newRole): Membership
    {
        Gate::forUser($user)->authorize('updateMember', $team);

        $membership = $team->memberships()
            ->where('model_type', $member::class)
            ->where('model_id', $member->id)
            ->firstOrFail();

        $oldRole = $membership->role;
        $membership->update(['role' => $newRole]);
        event(new TeamMemberRoleChanged($team, $member, $oldRole, $newRole));

        return $membership;
    }
}
