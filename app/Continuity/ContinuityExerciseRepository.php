<?php

namespace App\Continuity;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class ContinuityExerciseRepository
{
    /** @throws JsonException */
    public function current(): ContinuityExerciseDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/continuity-exercises.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Continuity Exercise definition must be a JSON object.');
        }

        return ContinuityExerciseDefinition::fromArray($definition);
    }
}
