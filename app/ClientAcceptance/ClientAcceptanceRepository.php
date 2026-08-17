<?php

namespace App\ClientAcceptance;

use RuntimeException;

final class ClientAcceptanceRepository
{
    public function current(): ClientAcceptanceDefinition
    {
        $path = resource_path('institution/client-acceptance.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read Client Acceptance definition at {$path}.");
        }

        return ClientAcceptanceDefinition::fromArray(json_decode(
            $contents,
            true,
            flags: JSON_THROW_ON_ERROR,
        ));
    }
}
