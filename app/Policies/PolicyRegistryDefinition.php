<?php

namespace App\Policies;

final readonly class PolicyRegistryDefinition
{
    /**
     * @param  list<PolicyDefinition>  $policies
     * @param  list<array<string, mixed>>  $approvalAdmissions
     * @param  list<array<string, mixed>>  $publicationRecords
     * @param  list<array<string, mixed>>  $activationRecords
     * @param  list<array<string, mixed>>  $exceptions
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $policies,
        public array $approvalAdmissions,
        public array $publicationRecords,
        public array $activationRecords,
        public array $exceptions,
        public array $evidenceRecords,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            policies: array_values(array_map(
                static fn (array $policy): PolicyDefinition => PolicyDefinition::fromArray($policy),
                $definition['policies'],
            )),
            approvalAdmissions: array_values($definition['policy_approval_admission_records'] ?? []),
            publicationRecords: array_values($definition['policy_publication_records'] ?? []),
            activationRecords: array_values($definition['policy_activation_records'] ?? []),
            exceptions: $definition['exceptions'],
            evidenceRecords: $definition['evidence_records'],
        );
    }
}
