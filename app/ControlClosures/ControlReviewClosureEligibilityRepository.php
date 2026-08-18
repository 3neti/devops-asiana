<?php

namespace App\ControlClosures;

final class ControlReviewClosureEligibilityRepository
{
    public function current(): ControlReviewClosureEligibilityDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-closure-eligibility.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Closure Eligibility definition.');
        }

        return ControlReviewClosureEligibilityDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
