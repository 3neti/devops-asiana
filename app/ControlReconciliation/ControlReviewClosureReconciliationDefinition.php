<?php

namespace App\ControlReconciliation;

final readonly class ControlReviewClosureReconciliationDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $reconciliations
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $reconciliations,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            reconciliations: array_values($definition['reconciliations']),
        );
    }
}
