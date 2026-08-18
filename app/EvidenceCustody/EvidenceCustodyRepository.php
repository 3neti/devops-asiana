<?php

namespace App\EvidenceCustody;

final class EvidenceCustodyRepository
{
    public function current(): EvidenceCustodyDefinition
    {
        $contents = file_get_contents(resource_path('institution/evidence-custody.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Evidence Custody definition.');
        }

        return EvidenceCustodyDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
