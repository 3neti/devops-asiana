<?php

namespace App\Incidents;

enum NotificationOutcome: string
{
    case Notified = 'notified';
    case NotRequired = 'not_required';
    case Pending = 'pending';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
