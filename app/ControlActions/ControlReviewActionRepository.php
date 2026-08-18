<?php

namespace App\ControlActions;

final class ControlReviewActionRepository
{
    public function current(): ControlReviewActionDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-actions.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Action definition.');
        }

        return ControlReviewActionDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
