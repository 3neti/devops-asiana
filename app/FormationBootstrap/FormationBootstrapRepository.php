<?php

namespace App\FormationBootstrap;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class FormationBootstrapRepository
{
    /** @throws JsonException */
    public function current(): FormationBootstrapDefinition
    {
        $definition = json_decode(File::get(resource_path('institution/formation-bootstrap.json')), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Formation Bootstrap definition must be a JSON object.');
        }

        return FormationBootstrapDefinition::fromArray($definition);
    }
}
