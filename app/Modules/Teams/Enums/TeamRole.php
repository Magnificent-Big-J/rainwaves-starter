<?php

namespace App\Modules\Teams\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    /** Can rename or delete the team, manage billing, and manage any member's role. */
    public function canManageTeam(): bool
    {
        return $this === self::Owner;
    }

    /** Can invite new members and manage/remove non-owner members. */
    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
