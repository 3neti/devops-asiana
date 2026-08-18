<?php

namespace App\ControlDecisions;

final readonly class ControlReviewClosureDecisionDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $decisions
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $decisions,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            decisions: array_values($definition['decisions']),
        );
    }
}
