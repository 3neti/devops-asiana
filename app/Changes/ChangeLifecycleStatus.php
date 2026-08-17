<?php

namespace App\Changes;

enum ChangeLifecycleStatus: string
{
    case Requested = 'requested';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Executing = 'executing';
    case Verifying = 'verifying';
    case Closed = 'closed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::UnderReview => 'Under Review',
            self::RolledBack => 'Rolled Back',
            default => str($this->value)->headline()->toString(),
        };
    }
}
