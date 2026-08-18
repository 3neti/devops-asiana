<?php

namespace App\ControlOutcomes;

final readonly class ControlReviewActionOutcomeDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $outcomes
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $outcomes,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            outcomes: array_values($definition['outcomes']),
        );
    }
}
