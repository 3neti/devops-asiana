<?php

namespace App\MatterEvents;

final class MatterEventRepository
{
    public function current(): MatterEventDefinition
    {
        $contents = file_get_contents(resource_path('institution/matter-events.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Matter Event definition.');
        }

        return MatterEventDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
