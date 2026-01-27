<?php

namespace App\Actions\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamInvitationSent;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class InviteTeamMember
{
    /**
     * Create and send a team invitation.
     */
    public function handle(User $user, Team $team, string $email, TeamRole $role = TeamRole::Member): TeamInvitation
    {
        Gate::forUser($user)->authorize('inviteMember', $team);

        $expiryMinutes = config('teams.invitations.default_expiry');

        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => $role,
            'invited_by' => $user->id,
            'expires_at' => $expiryMinutes ? now()->addMinutes($expiryMinutes) : null,
        ]);

        event(new TeamInvitationSent($invitation));
        Notification::route('mail', $email)->notify(new TeamInvitationNotification($invitation));

        return $invitation;
    }
}
