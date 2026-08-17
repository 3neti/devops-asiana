<?php

namespace App\Policies;

final readonly class PolicyRegistryDefinition
{
    /**
     * @param  list<PolicyDefinition>  $policies
     * @param  list<array<string, mixed>>  $exceptions
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $policies,
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
            exceptions: $definition['exceptions'],
            evidenceRecords: $definition['evidence_records'],
        );
    }
}
