<?php

namespace App\BreakGlassAccess;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class BreakGlassAccessRepository
{
    /** @throws JsonException */
    public function current(): BreakGlassAccessDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/break-glass-access.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Break-glass Access definition must be a JSON object.');
        }

        return BreakGlassAccessDefinition::fromArray($definition);
    }
}
