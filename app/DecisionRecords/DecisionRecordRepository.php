<?php

namespace App\DecisionRecords;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class DecisionRecordRepository
{
    /** @throws JsonException */
    public function current(): DecisionRecordDefinition
    {
        $definition = json_decode(
            File::get(resource_path('institution/decision-records.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Decision Record definition must be a JSON object.');
        }

        return DecisionRecordDefinition::fromArray($definition);
    }
}
