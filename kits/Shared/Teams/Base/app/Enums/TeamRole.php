<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Get all the permissions for this role.
     *
     * @return array<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => [
                'team:update',
                'team:delete',
                'member:add',
                'member:update',
                'member:remove',
                'invitation:create',
                'invitation:cancel',
            ],
            self::Admin => [
                'team:update',
                'invitation:create',
                'invitation:cancel',
            ],
            self::Member => [],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Member => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return [
            ['value' => self::Admin->value, 'label' => self::Admin->label()],
            ['value' => self::Member->value, 'label' => self::Member->label()],
        ];
    }
}
