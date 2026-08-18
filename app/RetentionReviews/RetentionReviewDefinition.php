<?php

namespace App\RetentionReviews;

final readonly class RetentionReviewDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $reviews
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $reviews,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            reviews: array_values($definition['reviews']),
        );
    }
}
