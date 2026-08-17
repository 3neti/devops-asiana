<?php

namespace App\ProductionAccess;

enum AccessGrantLifecycleStatus: string
{
    case Requested = 'requested';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Provisioned = 'provisioned';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved — Not Provisioned',
            self::Provisioned => 'Provisioned — Not Active',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
