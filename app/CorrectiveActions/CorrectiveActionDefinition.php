<?php

namespace App\CorrectiveActions;

final readonly class CorrectiveActionDefinition
{
    /**
     * @param  list<array{purpose: string, key: string, version: string, applies_to: list<string>, required_for_assignment: bool}>  $governingPolicies
     * @param  list<array{key: string, label: string, question: string}>  $recordRequirements
     * @param  list<array<string, mixed>>  $correctiveActions
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $correctiveActions,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicies: array_values($definition['governing_policies']),
            recordRequirements: array_values($definition['record_requirements']),
            correctiveActions: array_values($definition['corrective_actions']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
