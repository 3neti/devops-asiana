<?php

namespace App\ControlReconciliation;

final class ControlReviewClosureReconciliationRepository
{
    public function current(): ControlReviewClosureReconciliationDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-closure-reconciliations.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Closure Reconciliation definition.');
        }

        return ControlReviewClosureReconciliationDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
