<?php

namespace App\Engagements;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class EngagementRepository
{
    /**
     * @throws JsonException
     */
    public function current(): EngagementDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/engagements.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Engagement definition must be a JSON object.');
        }

        return EngagementDefinition::fromArray($definition);
    }
}
