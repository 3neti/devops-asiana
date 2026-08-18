<?php

namespace App\FormationCompletion;

final readonly class FormationCompletionDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  array<string, mixed>  $legalRequirementsRule
     * @param  array<string, mixed>  $capitalInitialization
     * @param  list<array<string, mixed>>  $commencementRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $legalRequirementsRule,
        public array $capitalInitialization,
        public array $commencementRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            legalRequirementsRule: $definition['legal_requirements_rule'],
            capitalInitialization: $definition['capital_initialization'],
            commencementRecords: array_values($definition['commencement_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
