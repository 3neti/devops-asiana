<?php

namespace App\ControlHistory;

final class InstitutionalControlHistoryAnchorVerificationRepository
{
    public function current(): InstitutionalControlHistoryAnchorVerificationDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-history-anchor-verification.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control History Anchor Verification definition.');
        }

        return InstitutionalControlHistoryAnchorVerificationDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
