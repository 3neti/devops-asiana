<?php

namespace App\RoleActivations;

final readonly class RoleActivationDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $activationRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $activationRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            activationRecords: array_values($definition['activation_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
