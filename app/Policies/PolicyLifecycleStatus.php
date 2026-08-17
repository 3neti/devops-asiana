<?php

namespace App\Policies;

enum PolicyLifecycleStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Effective = 'effective';
    case Superseded = 'superseded';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Effective => 'Effective',
            self::Superseded => 'Superseded',
            self::Retired => 'Retired',
        };
    }

    public function requiresImmutableContent(): bool
    {
        return $this !== self::Draft;
    }

    public function requiresApproval(): bool
    {
        return in_array($this, [self::Approved, self::Effective, self::Superseded, self::Retired], true);
    }
}
