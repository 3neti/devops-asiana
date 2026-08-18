<?php

namespace App\IdentityAndRoles;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class IdentityAndRoleRepository
{
    /** @throws JsonException */
    public function current(): IdentityAndRoleDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/identity-and-roles.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Identity and Role definition must be a JSON object.');
        }

        return IdentityAndRoleDefinition::fromArray($definition);
    }
}
