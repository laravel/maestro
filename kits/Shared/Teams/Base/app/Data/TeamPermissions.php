<?php

namespace App\Data;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, bool> */
readonly class TeamPermissions implements Arrayable
{
    public function __construct(
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
    ) {
        //
    }

    /**
     * @return array{canUpdateTeam: bool, canDeleteTeam: bool, canAddMember: bool, canUpdateMember: bool, canRemoveMember: bool, canCreateInvitation: bool, canCancelInvitation: bool}
     */
    public function toArray(): array
    {
        return [
            'canUpdateTeam' => $this->canUpdateTeam,
            'canDeleteTeam' => $this->canDeleteTeam,
            'canAddMember' => $this->canAddMember,
            'canUpdateMember' => $this->canUpdateMember,
            'canRemoveMember' => $this->canRemoveMember,
            'canCreateInvitation' => $this->canCreateInvitation,
            'canCancelInvitation' => $this->canCancelInvitation,
        ];
    }
}
