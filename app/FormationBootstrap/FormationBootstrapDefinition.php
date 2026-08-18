<?php

namespace App\FormationBootstrap;

final readonly class FormationBootstrapDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, string>>  $eligiblePolicyVersions
     * @param  array<string, mixed>  $consentRule
     * @param  list<array<string, mixed>>  $ratificationRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $eligiblePolicyVersions,
        public array $consentRule,
        public array $ratificationRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            eligiblePolicyVersions: array_values($definition['eligible_policy_versions']),
            consentRule: $definition['consent_rule'],
            ratificationRecords: array_values($definition['ratification_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
