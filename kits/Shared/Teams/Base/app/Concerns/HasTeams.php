<?php

namespace App\Concerns;

use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Team;
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
        return $this->belongsToMany(Team::class, 'team_members', 'model_id', 'team_id')
            ->wherePivot('model_type', static::class)
            ->withPivot(['role', 'is_default'])
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
            'model_id',
            'id',
            'id',
            'team_id'
        )->where('team_members.model_type', static::class)
            ->where('team_members.role', TeamRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'model_id')
            ->where('model_type', static::class);
    }

    /**
     * Get the user's current team (resolved from context).
     */
    public function currentTeam(): ?Team
    {
        return app()->bound('currentTeam') ? app('currentTeam') : $this->defaultTeam();
    }

    /**
     * Get the user's default team.
     */
    public function defaultTeam(): ?Team
    {
        return $this->teams()
            ->wherePivot('is_default', true)
            ->first();
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
     * Sets the given team as the default one.
     */
    public function setDefaultTeam(Team $team): void
    {
        if (! $this->belongsToTeam($team)) {
            return;
        }

        $this->memberships()->update(['is_default' => false]);

        $this->memberships()
            ->where('team_id', $team->id)
            ->update(['is_default' => true]);
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
