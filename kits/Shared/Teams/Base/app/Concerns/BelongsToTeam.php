<?php

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    /**
     * Get the team that the model belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope a query to only include records for the given team.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam(Builder $query, Team $team): Builder
    {
        return $query->where('team_id', $team->id);
    }
}
