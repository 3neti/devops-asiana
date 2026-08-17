<?php

namespace App\BreakGlassAccess;

enum BreakGlassLifecycleStatus: string
{
    case Requested = 'requested';
    case Authorized = 'authorized';
    case Activated = 'activated';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case UnderReview = 'under_review';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Authorized => 'Authorized',
            self::Activated => 'Activated',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
            self::UnderReview => 'Under Review',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
