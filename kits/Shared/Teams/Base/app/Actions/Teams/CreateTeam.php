<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamCreated;
use App\Events\Teams\TeamMemberAdded;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTeam
{
    /**
     * Create a new team and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Team
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $team = Team::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $membership = $team->memberships()->create([
                'user_id' => $user->id,
                'role' => TeamRole::Owner,
            ]);

            $user->update(['current_team_id' => $team->id]);

            event(new TeamCreated($team));
            event(new TeamMemberAdded($team, $user, $membership));

            return $team;
        });
    }
}
