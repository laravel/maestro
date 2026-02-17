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

        abort_if(! $user || ! $team || ! $user->belongsToTeam($team), 403);

        if ($minimumRole !== null) {
            $userRole = $user->teamRole($team);
            $requiredRole = TeamRole::tryFrom($minimumRole);

            abort_if($requiredRole === null || $userRole === null || ! $userRole->isAtLeast($requiredRole), 403);
        }

        // Only auto-switch "current_team", not "team" used in management routes
        if ($request->route('current_team') && ! $user->isCurrentTeam($team)) {
            $user->switchTeam($team);
        }

        return $next($request);
    }
}
