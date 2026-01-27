<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamMemberRemoved;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Teams\RemovedFromTeam;
use Illuminate\Support\Facades\Gate;

class RemoveTeamMember
{
    /**
     * Remove a member from the team.
     */
    public function handle(User $user, Team $team, User $member): void
    {
        Gate::forUser($user)->authorize('removeMember', $team);

        $team->memberships()
            ->where('user_id', $member->id)
            ->delete();

        if ($member->current_team_id === $team->id) {
            $member->update(['current_team_id' => null]);
        }

        event(new TeamMemberRemoved($team, $member));
        $member->notify(new RemovedFromTeam($team));
    }
}
