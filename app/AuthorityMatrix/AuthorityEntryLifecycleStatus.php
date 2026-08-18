<?php

namespace App\AuthorityMatrix;

enum AuthorityEntryLifecycleStatus: string
{
    case Design = 'design';
    case Approved = 'approved';
    case Active = 'active';
    case Superseded = 'superseded';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Design => 'Design',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::Superseded => 'Superseded',
            self::Retired => 'Retired',
        };
    }
}
