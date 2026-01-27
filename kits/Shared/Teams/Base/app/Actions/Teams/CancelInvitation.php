<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamInvitationCancelled;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class CancelInvitation
{
    /**
     * Cancel a pending team invitation.
     */
    public function handle(User $user, TeamInvitation $invitation): void
    {
        Gate::forUser($user)->authorize('cancelInvitation', $invitation->team);

        $invitation->delete();
        event(new TeamInvitationCancelled($invitation));
    }
}
