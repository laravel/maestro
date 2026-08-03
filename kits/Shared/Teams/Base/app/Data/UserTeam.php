<?php

namespace App\Data;

use Illuminate\Contracts\Support\Arrayable;

/** @implements Arrayable<string, bool|int|string|null> */
readonly class UserTeam implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $isPersonal,
        public ?string $role,
        public ?string $roleLabel,
        public ?bool $isCurrent = null,
    ) {
        //
    }

    /**
     * @return array{id: int, name: string, slug: string, isPersonal: bool, role: string|null, roleLabel: string|null, isCurrent: bool|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'isPersonal' => $this->isPersonal,
            'role' => $this->role,
            'roleLabel' => $this->roleLabel,
            'isCurrent' => $this->isCurrent,
        ];
    }
}
