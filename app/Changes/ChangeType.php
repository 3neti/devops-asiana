<?php

namespace App\Changes;

enum ChangeType: string
{
    case Standard = 'standard';
    case Normal = 'normal';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard Change',
            self::Normal => 'Normal Change',
            self::Emergency => 'Emergency Change',
        };
    }
}
