<?php

namespace App\Incidents;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class IncidentRepository
{
    /** @throws JsonException */
    public function current(): IncidentDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/incidents.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Incident definition must be a JSON object.');
        }

        return IncidentDefinition::fromArray($definition);
    }
}
