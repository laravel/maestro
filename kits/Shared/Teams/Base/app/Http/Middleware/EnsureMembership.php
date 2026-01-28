<?php

namespace App\Http\Middleware;

use App\Enums\TeamRole;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        $user = $request->user();
        $team = $request->route('current_team') ?? $request->route('team');

        if (is_string($team)) {
            $team = Team::where('slug', $team)->first();
        }

        if (! $user || ! $team) {
            abort(403);
        }

        if (! $user->belongsToTeam($team)) {
            abort(403);
        }

        if ($minimumRole !== null) {
            $userRole = $user->teamRole($team);
            $requiredRole = TeamRole::tryFrom($minimumRole);

            if ($requiredRole === null || $userRole === null) {
                abort(403);
            }

            if (! $userRole->isAtLeast($requiredRole)) {
                abort(403);
            }
        }

        if ($user->current_team_id !== $team->id) {
            $user->update(['current_team_id' => $team->id]);
        }

        return $next($request);
    }
}
