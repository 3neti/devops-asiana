<?php

namespace App\ProductionAccess;

final readonly class ResolvedProductionAccess
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $grantRequirements
     * @param  list<array<string, mixed>>  $accessGrants
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $readinessGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $grantRequirements,
        public array $accessGrants,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $conflicts,
        public array $decisionGaps,
        public array $evidenceGaps,
        public array $readinessGaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->decisionGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policies' => $this->governingPolicies,
            'grant_requirements' => $this->grantRequirements,
            'access_grants' => $this->accessGrants,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'access_grants' => count($this->accessGrants),
                'evidence_records' => count($this->evidenceRecords),
                'by_lifecycle_status' => $this->lifecycleCounts,
                'active_authority' => count(array_filter(
                    $this->accessGrants,
                    static fn (array $grant): bool => ($grant['may_use_access'] ?? false) === true,
                )),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No Access Without Authority.',
                'Technical possession of credentials is not authority to use them.',
                'Approval, provisioning, verification, activation, review, and revocation remain separate records.',
                'Every grant is named, least-privileged, time-bounded, Engagement-scoped, and evidenced.',
                'No credential secret belongs in the institutional repository.',
            ],
            'boundary' => 'Break-glass access requires a separate emergency path and is not modeled as an ordinary Access Grant.',
        ];
    }
}
