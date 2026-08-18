<?php

namespace App\GovernanceMeetings;

final readonly class GovernanceMeetingDefinition
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, string>>  $meetingRequirements
     * @param  array<string, mixed>  $decisionRules
     * @param  list<array<string, string>>  $reservedMatterCatalog
     * @param  list<array<string, mixed>>  $meetings
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $meetingRequirements,
        public array $decisionRules,
        public array $reservedMatterCatalog,
        public array $meetings,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicies: array_values($definition['governing_policies']),
            meetingRequirements: array_values($definition['meeting_requirements']),
            decisionRules: $definition['decision_rules'],
            reservedMatterCatalog: array_values($definition['reserved_matter_catalog']),
            meetings: array_values($definition['meeting_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
