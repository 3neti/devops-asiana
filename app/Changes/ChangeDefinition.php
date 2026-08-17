<?php

namespace App\Changes;

final readonly class ChangeDefinition
{
    /**
     * @param  list<array{purpose: string, key: string, version: string, required_for_execution: bool}>  $governingPolicies
     * @param  list<array{key: string, label: string, question: string}>  $recordRequirements
     * @param  list<array<string, mixed>>  $changeRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $changeRecords,
        public array $evidenceRecords,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicies: array_values($definition['governing_policies']),
            recordRequirements: array_values($definition['record_requirements']),
            changeRecords: array_values($definition['change_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
