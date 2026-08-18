<?php

namespace App\GovernanceMeetings;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class GovernanceMeetingRepository
{
    /** @throws JsonException */
    public function current(): GovernanceMeetingDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/governance-meetings.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Governance Meeting definition must be a JSON object.');
        }

        return GovernanceMeetingDefinition::fromArray($definition);
    }
}
