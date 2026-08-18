<?php

namespace App\DecisionRecords;

final readonly class DecisionRecordDefinition
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, string>>  $recordRequirements
     * @param  list<array<string, mixed>>  $collectiveAdmissions
     * @param  list<array<string, mixed>>  $decisions
     * @param  list<array<string, mixed>>  $executions
     * @param  list<array<string, mixed>>  $verifications
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $collectiveAdmissions,
        public array $decisions,
        public array $executions,
        public array $verifications,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicies: array_values($definition['governing_policies']),
            recordRequirements: array_values($definition['record_requirements']),
            collectiveAdmissions: array_values($definition['collective_admission_records'] ?? []),
            decisions: array_values($definition['decision_records']),
            executions: array_values($definition['execution_records']),
            verifications: array_values($definition['verification_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
