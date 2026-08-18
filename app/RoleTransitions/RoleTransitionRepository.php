<?php

namespace App\RoleTransitions;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class RoleTransitionRepository
{
    /** @throws JsonException */
    public function current(): RoleTransitionDefinition
    {
        $definition = json_decode(File::get(resource_path('institution/role-transitions.json')), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Role Transition definition must be a JSON object.');
        }

        return RoleTransitionDefinition::fromArray($definition);
    }
}
