<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamUpdated;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateTeam
{
    public function __construct(protected ValidateTeamName $validateTeamName) {}

    /**
     * Update the team details.
     */
    public function handle(User $user, Team $team, string $name): Team
    {
        Gate::forUser($user)->authorize('update', $team);

        if (! $team->is_personal) {
            $this->validateTeamName->handle($name);
        }

        $team->update(['name' => $name]);
        event(new TeamUpdated($team));

        return $team;
    }
}
