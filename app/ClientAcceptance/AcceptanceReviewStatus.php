<?php

namespace App\ClientAcceptance;

enum AcceptanceReviewStatus: string
{
    case Identified = 'identified';
    case UnderReview = 'under_review';
    case DecisionRecorded = 'decision_recorded';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Identified => 'Identified',
            self::UnderReview => 'Under Review',
            self::DecisionRecorded => 'Decision Recorded',
            self::Expired => 'Expired',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
