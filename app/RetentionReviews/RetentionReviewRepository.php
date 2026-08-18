<?php

namespace App\RetentionReviews;

final class RetentionReviewRepository
{
    public function current(): RetentionReviewDefinition
    {
        $contents = file_get_contents(resource_path('institution/retention-reviews.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Retention Review definition.');
        }

        return RetentionReviewDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
