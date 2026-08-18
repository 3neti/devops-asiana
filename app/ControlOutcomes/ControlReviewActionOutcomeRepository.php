<?php

namespace App\ControlOutcomes;

final class ControlReviewActionOutcomeRepository
{
    public function current(): ControlReviewActionOutcomeDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-action-outcomes.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Action Outcome definition.');
        }

        return ControlReviewActionOutcomeDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
