<?php

namespace App\ControlHistory;

final class InstitutionalControlHistoryVerificationEvidenceLinkRepository
{
    public function current(): InstitutionalControlHistoryVerificationEvidenceLinkDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-history-verification-evidence-links.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control History Verification Evidence Link definition.');
        }

        return InstitutionalControlHistoryVerificationEvidenceLinkDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
