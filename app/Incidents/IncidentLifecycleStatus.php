<?php

namespace App\Incidents;

enum IncidentLifecycleStatus: string
{
    case Detected = 'detected';
    case Triaged = 'triaged';
    case Declared = 'declared';
    case Active = 'active';
    case Contained = 'contained';
    case Recovering = 'recovering';
    case Monitoring = 'monitoring';
    case ServiceRestored = 'service_restored';
    case UnderReview = 'under_review';
    case Closed = 'closed';
    case FalsePositive = 'false_positive';

    public function label(): string
    {
        return match ($this) {
            self::Detected => 'Detected',
            self::Triaged => 'Triaged',
            self::Declared => 'Declared',
            self::Active => 'Active Response',
            self::Contained => 'Contained',
            self::Recovering => 'Recovering',
            self::Monitoring => 'Monitoring',
            self::ServiceRestored => 'Service Restored',
            self::UnderReview => 'Under Review',
            self::Closed => 'Closed',
            self::FalsePositive => 'False Positive',
        };
    }
}
