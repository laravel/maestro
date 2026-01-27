<?php

namespace App\Listeners;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeam
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $team = Team::create([
            'name' => $user->name."'s Team",
            'is_personal' => true,
        ]);

        $team->members()->attach($user, [
            'model_type' => $user::class,
            'role' => TeamRole::Owner->value,
            'is_default' => true,
        ]);
    }
}
