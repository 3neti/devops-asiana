<?php

namespace App\Policies;

use RuntimeException;

final class PolicyRegistryRepository
{
    public function current(): PolicyRegistryDefinition
    {
        $path = resource_path('institution/policies.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read policy registry at {$path}.");
        }

        return PolicyRegistryDefinition::fromArray(json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR,
        ));
    }
}
