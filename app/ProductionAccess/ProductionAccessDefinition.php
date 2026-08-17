<?php

namespace App\ProductionAccess;

final readonly class ProductionAccessDefinition
{
    /**
     * @param  list<array{purpose: string, key: string, version: string, required_for_activation: bool}>  $governingPolicies
     * @param  list<array{key: string, label: string, question: string}>  $grantRequirements
     * @param  list<array<string, mixed>>  $accessGrants
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $grantRequirements,
        public array $accessGrants,
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
            grantRequirements: array_values($definition['grant_requirements']),
            accessGrants: array_values($definition['access_grants']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
