<?php

namespace App\GovernanceMeetings;

enum GovernanceMeetingLifecycleStatus: string
{
    case Scheduled = 'scheduled';
    case Convened = 'convened';
    case Deliberating = 'deliberating';
    case Concluded = 'concluded';
    case Adjourned = 'adjourned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Convened => 'Convened',
            self::Deliberating => 'Deliberating',
            self::Concluded => 'Concluded',
            self::Adjourned => 'Adjourned',
            self::Cancelled => 'Cancelled',
        };
    }
}
