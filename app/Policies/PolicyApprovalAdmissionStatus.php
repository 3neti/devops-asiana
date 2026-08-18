<?php

namespace App\Policies;

enum PolicyApprovalAdmissionStatus: string
{
    case Proposed = 'proposed';
    case Admitted = 'admitted';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposed',
            self::Admitted => 'Admitted',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
