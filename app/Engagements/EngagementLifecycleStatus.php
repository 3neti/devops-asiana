<?php

namespace App\Engagements;

enum EngagementLifecycleStatus: string
{
    case Proposed = 'proposed';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Open = 'open';
    case Suspended = 'suspended';
    case Closed = 'closed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved — Not Open',
            self::Open => 'Open',
            self::Suspended => 'Suspended',
            self::Closed => 'Closed',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
