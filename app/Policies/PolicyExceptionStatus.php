<?php

namespace App\Policies;

enum PolicyExceptionStatus: string
{
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Active = 'active';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function requiresApproval(): bool
    {
        return in_array($this, [self::Approved, self::Active, self::Expired], true);
    }
}
