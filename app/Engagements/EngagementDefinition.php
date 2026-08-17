<?php

namespace App\Engagements;

final readonly class EngagementDefinition
{
    /**
     * @param  list<array{purpose: string, key: string, version: string, required_for_opening: bool}>  $governingPolicies
     * @param  list<array{key: string, label: string, question: string}>  $openingRequirements
     * @param  list<array<string, mixed>>  $engagements
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $openingRequirements,
        public array $engagements,
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
            openingRequirements: array_values($definition['opening_requirements']),
            engagements: array_values($definition['engagements']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
