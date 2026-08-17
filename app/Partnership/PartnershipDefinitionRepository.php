<?php

namespace App\Partnership;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class PartnershipDefinitionRepository
{
    /**
     * @throws JsonException
     */
    public function current(): PartnershipDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/partnership.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The partnership definition must be a JSON object.');
        }

        /** @var array{schema_version: int, formation: array<string, mixed>, constitution: array<string, mixed>, decisions: list<array{key: string, label: string, institutional_state: string, legal_state: string, statement: string}>} $definition */
        return PartnershipDefinition::fromArray($definition);
    }
}
