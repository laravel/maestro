<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->createPersonalTeam($user);

            return $user;
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createPersonalTeam(User $user): void
    {
        $team = Team::create([
            'name' => $user->name."'s Team",
            'is_personal' => true,
        ]);

        $team->members()->attach($user, [
            'model_type' => $user::class,
            'role' => TeamRole::Owner->value,
        ]);

        $user->update(['current_team_id' => $team->id]);
    }
}
