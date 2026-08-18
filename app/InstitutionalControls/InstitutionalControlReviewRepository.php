<?php

namespace App\InstitutionalControls;

final class InstitutionalControlReviewRepository
{
    public function current(): InstitutionalControlReviewDefinition
    {
        $contents = file_get_contents(resource_path('institution/institutional-control-review.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Institutional Control Review definition.');
        }

        return InstitutionalControlReviewDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
