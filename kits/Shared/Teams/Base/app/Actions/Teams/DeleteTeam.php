<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamDeleted;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteTeam
{
    /**
     * Delete the team.
     */
    public function handle(User $user, Team $team): void
    {
        Gate::forUser($user)->authorize('delete', $team);


        User::where('current_team_id', $team->id)->update(['current_team_id' => null]);
        $team->invitations()->delete();
        $team->memberships()->delete();
        $team->delete();

        event(new TeamDeleted($team));
    }
}
