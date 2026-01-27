<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

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
            self::Member => [
                'invitation:create',
            ],
            self::Viewer => [],
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
}
