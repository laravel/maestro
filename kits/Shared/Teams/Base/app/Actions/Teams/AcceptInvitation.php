<?php

namespace App\Actions\Teams;

use App\Events\Teams\TeamInvitationAccepted;
use App\Events\Teams\TeamMemberAdded;
use App\Models\Membership;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\InvitationAccepted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    /**
     * Accept a team invitation and create membership.
     */
    public function handle(User $user, TeamInvitation $invitation): Membership
    {
        $this->validate($user, $invitation);

        return DB::transaction(function () use ($user, $invitation) {
            $team = $invitation->team;

            $membership = $team->memberships()->create([
                'model_type' => $user::class,
                'model_id' => $user->id,
                'role' => $invitation->role,
            ]);

            $invitation->update(['accepted_at' => now()]);
            $user->update(['current_team_id' => $team->id]);

            event(new TeamInvitationAccepted($invitation, $user));
            event(new TeamMemberAdded($team, $user, $membership));

            if (config()->boolean('teams.invitations.notify_on_join')) {
                $team->owner()?->notify(new InvitationAccepted($team, $user));
            }

            return $membership;
        });
    }

    /**
     * Validate the invitation can be accepted by the user.
     *
     * @throws ValidationException
     */
    private function validate(User $user, TeamInvitation $invitation): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation has already been accepted.')],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation has expired.')],
            ]);
        }

        if ($invitation->email !== $user->email) {
            throw ValidationException::withMessages([
                'invitation' => [__('This invitation was sent to a different email address.')],
            ]);
        }
    }
}
