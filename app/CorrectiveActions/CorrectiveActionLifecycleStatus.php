<?php

namespace App\CorrectiveActions;

enum CorrectiveActionLifecycleStatus: string
{
    case Proposed = 'proposed';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::PendingVerification => 'Pending Verification',
            self::Verified => 'Verified',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
            self::Superseded => 'Superseded',
        };
    }
}
