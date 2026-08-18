<?php

namespace App\Continuity;

enum ContinuityExerciseLifecycleStatus: string
{
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case AwaitingVerification = 'awaiting_verification';
    case Verified = 'verified';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::Approved => 'Approved',
            self::Scheduled => 'Scheduled',
            self::InProgress => 'In Progress',
            self::AwaitingVerification => 'Awaiting Verification',
            self::Verified => 'Verified',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }
}
