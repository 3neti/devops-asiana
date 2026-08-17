<?php

namespace App\ProductionAccess;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class ProductionAccessRepository
{
    /**
     * @throws JsonException
     */
    public function current(): ProductionAccessDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/production-access.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Production Access definition must be a JSON object.');
        }

        return ProductionAccessDefinition::fromArray($definition);
    }
}
