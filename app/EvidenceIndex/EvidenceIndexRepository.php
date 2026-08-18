<?php

namespace App\EvidenceIndex;

final class EvidenceIndexRepository
{
    public function current(): EvidenceIndexDefinition
    {
        $contents = file_get_contents(resource_path('institution/evidence-index.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Evidence Index definition.');
        }

        return EvidenceIndexDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
