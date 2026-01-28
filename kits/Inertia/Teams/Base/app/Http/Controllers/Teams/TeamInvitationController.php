<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Events\Teams\TeamInvitationAccepted;
use App\Events\Teams\TeamInvitationCancelled;
use App\Events\Teams\TeamInvitationSent;
use App\Events\Teams\TeamMemberAdded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Notifications\Teams\InvitationAccepted;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class TeamInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $expiryMinutes = config('teams.invitations.default_expiry');

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => TeamRole::from($request->validated('role')),
            'invited_by' => $request->user()->id,
            'expires_at' => $expiryMinutes ? now()->addMinutes($expiryMinutes) : null,
        ]);

        event(new TeamInvitationSent($invitation));
        Notification::route('mail', $invitation->email)->notify(new TeamInvitationNotification($invitation));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Request $request, Team $team, TeamInvitation $invitation): RedirectResponse
    {
        Gate::authorize('cancelInvitation', $invitation->team);

        $invitation->delete();
        event(new TeamInvitationCancelled($invitation));

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        $this->validateInvitation($user, $invitation);

        DB::transaction(function () use ($user, $invitation) {
            $team = $invitation->team;

            $membership = $team->memberships()->create([
                'user_id' => $user->id,
                'role' => $invitation->role,
            ]);

            $invitation->update(['accepted_at' => now()]);
            $user->update(['current_team_id' => $team->id]);

            event(new TeamInvitationAccepted($invitation, $user));
            event(new TeamMemberAdded($team, $user, $membership));

            if (config()->boolean('teams.invitations.notify_on_join')) {
                $team->owner()?->notify(new InvitationAccepted($team, $user));
            }
        });

        return to_route('dashboard', ['current_team' => $invitation->team->slug]);
    }

    /**
     * Validate the invitation can be accepted by the user.
     *
     * @throws ValidationException
     */
    private function validateInvitation($user, TeamInvitation $invitation): void
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
