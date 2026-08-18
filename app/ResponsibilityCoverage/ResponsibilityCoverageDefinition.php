<?php

namespace App\ResponsibilityCoverage;

final readonly class ResponsibilityCoverageDefinition
{
    /**
     * @param  list<array<string, mixed>>  $requirements
     * @param  list<array{key: string, left_requirement_key: string, right_requirement_key: string, reason: string}>  $separationConstraints
     */
    public function __construct(
        public int $schemaVersion,
        public int $concentrationReviewThreshold,
        public array $requirements,
        public array $separationConstraints,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            concentrationReviewThreshold: $definition['concentration_review_threshold'],
            requirements: array_values($definition['requirements']),
            separationConstraints: array_values($definition['separation_constraints']),
        );
    }
}
