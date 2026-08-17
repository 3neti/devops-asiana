<?php

namespace App\Partnership;

enum ResolutionStatus: string
{
    case Resolved = 'resolved';
    case Unresolved = 'unresolved';
    case CounselReview = 'counsel_review';
    case NotYetReady = 'not_yet_ready';

    public function label(): string
    {
        return match ($this) {
            self::Resolved => 'Resolved',
            self::Unresolved => 'Unresolved',
            self::CounselReview => 'Counsel review',
            self::NotYetReady => 'Not yet ready',
        };
    }
}
