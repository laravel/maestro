<?php

namespace App\Http\Requests\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Rules\UniqueTeamInvitation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTeamInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', new UniqueTeamInvitation($this->team())],
            'role' => ['required', 'string', Rule::enum(TeamRole::class)],
        ];
    }

    private function team(): Team
    {
        $team = $this->route('team');

        if (! $team instanceof Team) {
            abort(404);
        }

        return $team;
    }
}
