<?php

namespace App\Http\Requests\Teams;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class DeleteTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('team'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'new_current_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $team = $this->route('team');
                $user = $this->user();

                if ($this->input('name') !== $team->name) {
                    $validator->errors()->add('name', 'The team name does not match.');
                }

                $isDeletingCurrentTeam = $user->isCurrentTeam($team);
                if ($isDeletingCurrentTeam && ! $this->input('new_current_team_id')) {
                    $validator->errors()->add('new_current_team_id', 'You must select a new current team.');
                }

                if ($this->input('new_current_team_id')) {
                    $belongsToTeam = $user->teams()->where('teams.id', $this->input('new_current_team_id'))->exists();

                    if (! $belongsToTeam) {
                        $validator->errors()->add('new_current_team_id', 'You do not belong to this team.');
                    }
                }
            },
        ];
    }
}
