<?php

namespace App\Matters;

final class MatterRepository
{
    public function current(): MatterDefinition
    {
        $contents = file_get_contents(resource_path('institution/matters.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Matter definition.');
        }

        return MatterDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
