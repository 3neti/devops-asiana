<?php

namespace App\ControlHistory;

final class InstitutionalControlHistoryVerificationLinkReconciliationRepository
{
    public function current(): InstitutionalControlHistoryVerificationLinkReconciliationDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-history-verification-link-reconciliations.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control History Verification Link Reconciliation definition.');
        }

        return InstitutionalControlHistoryVerificationLinkReconciliationDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
