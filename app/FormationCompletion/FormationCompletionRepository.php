<?php

namespace App\FormationCompletion;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class FormationCompletionRepository
{
    /** @throws JsonException */
    public function current(): FormationCompletionDefinition
    {
        $definition = json_decode(File::get(resource_path('institution/formation-completion.json')), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Formation Completion definition must be a JSON object.');
        }

        return FormationCompletionDefinition::fromArray($definition);
    }
}
