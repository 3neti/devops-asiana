<?php

namespace App\ClientAcceptance;

final readonly class ClientAcceptanceDefinition
{
    /**
     * @param  array{key: string, version: string}  $governingPolicy
     * @param  list<array{key: string, label: string, question: string}>  $requiredAssessments
     * @param  list<array<string, mixed>>  $prospectiveClients
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicy,
        public array $requiredAssessments,
        public array $prospectiveClients,
        public array $evidenceRecords,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicy: $definition['governing_policy'],
            requiredAssessments: array_values($definition['required_assessments']),
            prospectiveClients: array_values($definition['prospective_clients']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
