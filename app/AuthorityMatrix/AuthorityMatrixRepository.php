<?php

namespace App\AuthorityMatrix;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class AuthorityMatrixRepository
{
    /** @throws JsonException */
    public function current(): AuthorityMatrixDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/authority-matrix.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Authority Matrix definition must be a JSON object.');
        }

        return AuthorityMatrixDefinition::fromArray($definition);
    }
}
