<?php

namespace App\RoleActivations;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class RoleActivationRepository
{
    /** @throws JsonException */
    public function current(): RoleActivationDefinition
    {
        $definition = json_decode(File::get(resource_path('institution/role-activations.json')), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Role Activation definition must be a JSON object.');
        }

        return RoleActivationDefinition::fromArray($definition);
    }
}
