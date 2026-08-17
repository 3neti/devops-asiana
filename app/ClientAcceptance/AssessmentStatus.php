<?php

namespace App\ClientAcceptance;

enum AssessmentStatus: string
{
    case Satisfactory = 'satisfactory';
    case ConcernIdentified = 'concern_identified';
    case NotApplicable = 'not_applicable';
    case Unresolved = 'unresolved';

    public function label(): string
    {
        return match ($this) {
            self::Satisfactory => 'Satisfactory',
            self::ConcernIdentified => 'Concern Identified',
            self::NotApplicable => 'Not Applicable',
            self::Unresolved => 'Unresolved',
        };
    }
}
