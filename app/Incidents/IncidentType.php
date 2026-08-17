<?php

namespace App\Incidents;

enum IncidentType: string
{
    case Operational = 'operational';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Operational Incident',
            self::Security => 'Security Incident',
        };
    }
}
