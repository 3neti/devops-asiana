<?php

namespace App\DecisionRecords;

enum DecisionRecordLifecycleStatus: string
{
    case Proposed = 'proposed';
    case UnderReview = 'under_review';
    case Decided = 'decided';
    case Effective = 'effective';
    case Superseded = 'superseded';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::UnderReview => 'Under Review',
            self::Decided => 'Decided',
            self::Effective => 'Effective',
            self::Superseded => 'Superseded',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
