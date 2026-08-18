<?php

namespace App\ControlHistory;

final class InstitutionalControlHistoryRepository
{
    public function current(): InstitutionalControlHistoryDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-history.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Institutional Control History definition.');
        }

        return InstitutionalControlHistoryDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
