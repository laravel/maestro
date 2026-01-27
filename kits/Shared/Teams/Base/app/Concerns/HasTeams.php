<?php

namespace App\Concerns;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasTeams
{
    /**
     * Get all of the teams the user belongs to.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all of the teams the user owns.
     *
     * @return HasManyThrough<Team, Membership, $this>
     */
    public function ownedTeams(): HasManyThrough
    {
        return $this->hasManyThrough(
            Team::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'team_id'
        )->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Switch to the given team.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->update(['current_team_id' => $team->id]);

        return true;
    }

    /**
     * Get the user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Determine if the user belongs to the given team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Get the user's role on the given team.
     */
    public function teamRole(Team $team): ?TeamRole
    {
        $membership = $this->memberships()
            ->where('team_id', $team->id)
            ->first();

        return $membership?->role;
    }

    /**
     * Determine if the user is the owner of the given team.
     */
    public function isOwnerOf(Team $team): bool
    {
        return $this->teamRole($team) === TeamRole::Owner;
    }

    /**
     * Determine if the user has the given permission on the team.
     */
    public function hasTeamPermission(Team $team, string $permission): bool
    {
        $role = $this->teamRole($team);

        return $role?->hasPermission($permission) ?? false;
    }
}
