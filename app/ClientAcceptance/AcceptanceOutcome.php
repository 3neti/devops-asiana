<?php

namespace App\ClientAcceptance;

enum AcceptanceOutcome: string
{
    case Accepted = 'accepted';
    case AcceptedWithConditions = 'accepted_with_conditions';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Accepted',
            self::AcceptedWithConditions => 'Accepted with Conditions',
            self::Rejected => 'Rejected',
        };
    }

    public function permitsEngagementConsideration(): bool
    {
        return $this !== self::Rejected;
    }
}
