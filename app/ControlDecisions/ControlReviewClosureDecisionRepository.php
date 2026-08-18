<?php

namespace App\ControlDecisions;

final class ControlReviewClosureDecisionRepository
{
    public function current(): ControlReviewClosureDecisionDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-closure-decisions.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Closure Decision definition.');
        }

        return ControlReviewClosureDecisionDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
