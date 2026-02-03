<?php

use App\Events\Teams\TeamInvitationAccepted;
use App\Events\Teams\TeamMemberAdded;
use App\Models\TeamInvitation;
use App\Notifications\Teams\InvitationAccepted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    public TeamInvitation $invitation;

    public function mount(TeamInvitation $invitation): void
    {
        $this->invitation = $invitation;

        $this->acceptInvitation();
    }

    public function acceptInvitation(): void
    {
        $user = Auth::user();
        $this->validateInvitation($user, $this->invitation);

        DB::transaction(function () use ($user) {
            $team = $this->invitation->team;

            $membership = $team->memberships()->create([
                'user_id' => $user->id,
                'role' => $this->invitation->role,
            ]);

            $this->invitation->update(['accepted_at' => now()]);
            $user->switchTeam($team);

            event(new TeamInvitationAccepted($this->invitation, $user));
            event(new TeamMemberAdded($team, $user, $membership));

            if (config()->boolean('teams.invitations.notify_on_join')) {
                $team->owner()?->notify(new InvitationAccepted($team, $user));
            }
        });

        $this->redirectRoute('dashboard');
    }

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
}; ?>

<div></div>
