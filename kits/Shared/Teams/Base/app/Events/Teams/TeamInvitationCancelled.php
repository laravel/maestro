<?php

namespace App\Events\Teams;

use App\Models\TeamInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public TeamInvitation $invitation,
    ) {}
}
