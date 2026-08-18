<?php

namespace App\ResponsibilityCoverage;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class ResponsibilityCoverageRepository
{
    /** @throws JsonException */
    public function current(): ResponsibilityCoverageDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/responsibility-coverage.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Responsibility Coverage definition must be a JSON object.');
        }

        return ResponsibilityCoverageDefinition::fromArray($definition);
    }
}
