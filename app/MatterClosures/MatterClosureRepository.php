<?php

namespace App\MatterClosures;

final class MatterClosureRepository
{
    public function current(): MatterClosureDefinition
    {
        $contents = file_get_contents(resource_path('institution/matter-closures.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Matter Closure definition.');
        }

        return MatterClosureDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
