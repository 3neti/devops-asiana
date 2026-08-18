<?php

namespace App\IdentityAndRoles;

enum AssignmentLifecycleStatus: string
{
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Active = 'active';
    case Suspended = 'suspended';
    case Ended = 'ended';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Ended => 'Ended',
            self::Revoked => 'Revoked',
        };
    }
}
