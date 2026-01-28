<?php

namespace App\Actions\Teams;

use App\Support\ReservedNamesList;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ValidateTeamName
{
    /**
     * Validate that the team name is not reserved.
     *
     * @throws ValidationException
     */
    public function handle(string $name): void
    {
        $slug = Str::slug($name);

        if (in_array($slug, ReservedNamesList::all(), true)) {
            throw ValidationException::withMessages([
                'name' => __('This team name is reserved and cannot be used.'),
            ]);
        }
    }
}
