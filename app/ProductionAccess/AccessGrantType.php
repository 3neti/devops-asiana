<?php

namespace App\ProductionAccess;

enum AccessGrantType: string
{
    case Standard = 'standard';
    case Privileged = 'privileged';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard Access',
            self::Privileged => 'Privileged Access',
        };
    }
}
