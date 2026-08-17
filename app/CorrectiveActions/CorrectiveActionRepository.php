<?php

namespace App\CorrectiveActions;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class CorrectiveActionRepository
{
    /** @throws JsonException */
    public function current(): CorrectiveActionDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/corrective-actions.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Corrective Action definition must be a JSON object.');
        }

        return CorrectiveActionDefinition::fromArray($definition);
    }
}
