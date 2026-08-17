<?php

namespace App\Engagements;

enum EngagementApprovalOutcome: string
{
    case Approved = 'approved';
    case ApprovedWithConditions = 'approved_with_conditions';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::ApprovedWithConditions => 'Approved with Conditions',
            self::Rejected => 'Rejected',
        };
    }

    public function permitsOpening(): bool
    {
        return $this !== self::Rejected;
    }
}
