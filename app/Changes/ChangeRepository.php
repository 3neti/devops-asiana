<?php

namespace App\Changes;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class ChangeRepository
{
    /**
     * @throws JsonException
     */
    public function current(): ChangeDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/changes.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Change definition must be a JSON object.');
        }

        return ChangeDefinition::fromArray($definition);
    }
}
