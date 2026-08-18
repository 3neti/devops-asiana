<?php

namespace App\ClientMandates;

final class ClientMandateRepository
{
    public function current(): ClientMandateDefinition
    {
        $contents = file_get_contents(resource_path('institution/client-mandates.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Client Mandate definition.');
        }
        $definition = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return ClientMandateDefinition::fromArray($definition);
    }
}
