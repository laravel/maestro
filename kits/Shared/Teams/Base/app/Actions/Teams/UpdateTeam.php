<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamUpdated;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateTeam
{
    /**
     * Update the team details.
     */
    public function handle(User $user, Team $team, string $name): Team
    {
        Gate::forUser($user)->authorize('update', $team);

        $team->update(['name' => $name]);
        event(new TeamUpdated($team));

        return $team;
    }
}
