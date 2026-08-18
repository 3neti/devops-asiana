<?php

namespace App\RetentionFindings;

final class RetentionFindingLinkRepository
{
    public function current(): RetentionFindingLinkDefinition
    {
        $contents = file_get_contents(resource_path('institution/retention-finding-links.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Retention Finding Link definition.');
        }

        return RetentionFindingLinkDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
