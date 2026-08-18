<?php

namespace App\Matters;

final readonly class ResolvedMatters
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $matters
     * @param  list<array<string, mixed>>  $accountabilityProjections
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $engagementGaps
     * @param  list<array{code: string, message: string}>  $responsibilityGaps
     * @param  list<array{code: string, message: string}>  $scopeGaps
     * @param  list<array{code: string, message: string}>  $riskGaps
     * @param  list<array{code: string, message: string}>  $escalationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $matters,
        public array $accountabilityProjections,
        public array $evidenceRecords,
        public array $conflicts,
        public array $engagementGaps,
        public array $responsibilityGaps,
        public array $scopeGaps,
        public array $riskGaps,
        public array $escalationGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->engagementGaps !== [] || $this->responsibilityGaps !== [] || $this->scopeGaps !== [] || $this->riskGaps !== [] || $this->escalationGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'matters' => $this->matters,
            'accountability_projections' => $this->accountabilityProjections,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'matters' => count($this->matters),
                'accountability_projections' => count($this->accountabilityProjections),
                'active_matters' => count(array_filter($this->accountabilityProjections, static fn (array $matter): bool => ($matter['may_perform_matter_work'] ?? false) === true)),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'engagement_gaps' => $this->engagementGaps,
                'responsibility_gaps' => $this->responsibilityGaps,
                'scope_gaps' => $this->scopeGaps,
                'risk_gaps' => $this->riskGaps,
                'escalation_gaps' => $this->escalationGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A Matter is bounded professional work inside an Engagement, not a replacement for the Client relationship or Engagement.',
                'Every material Matter has exactly one Responsible Partner.',
                'Responsible Partner accountability does not replace action-specific Firm Authority or Client Mandate.',
                'Risk ownership, acceptance, escalation, and Evidence remain explicit facts.',
                'A Matter projection never creates an Engagement or permits Client work by itself.',
            ],
        ];
    }
}
