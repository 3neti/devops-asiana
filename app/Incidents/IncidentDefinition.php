<?php

namespace App\Incidents;

final readonly class IncidentDefinition
{
    /**
     * @param  list<array{purpose: string, key: string, version: string, applies_to: string, required_for_declaration: bool}>  $governingPolicies
     * @param  list<array{key: string, label: string, question: string}>  $recordRequirements
     * @param  list<array<string, mixed>>  $incidentRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $incidentRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicies: array_values($definition['governing_policies']),
            recordRequirements: array_values($definition['record_requirements']),
            incidentRecords: array_values($definition['incident_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
