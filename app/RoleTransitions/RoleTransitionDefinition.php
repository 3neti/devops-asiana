<?php

namespace App\RoleTransitions;

final readonly class RoleTransitionDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $transitionRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $transitionRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            transitionRecords: array_values($definition['transition_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
