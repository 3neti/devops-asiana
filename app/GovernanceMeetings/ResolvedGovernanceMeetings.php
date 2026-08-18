<?php

namespace App\GovernanceMeetings;

final readonly class ResolvedGovernanceMeetings
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, string>>  $meetingRequirements
     * @param  array<string, mixed>  $decisionRules
     * @param  list<array<string, string>>  $reservedMatterCatalog
     * @param  list<array<string, mixed>>  $governingPartners
     * @param  list<array<string, mixed>>  $meetings
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $lifecycleCounts
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $meetingGaps
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $readinessGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $meetingRequirements,
        public array $decisionRules,
        public array $reservedMatterCatalog,
        public array $governingPartners,
        public array $meetings,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $outcomeCounts,
        public array $conflicts,
        public array $meetingGaps,
        public array $authorityGaps,
        public array $evidenceGaps,
        public array $readinessGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->meetingGaps !== [], $this->authorityGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policies' => $this->governingPolicies,
            'meeting_requirements' => $this->meetingRequirements,
            'decision_rules' => $this->decisionRules,
            'reserved_matter_catalog' => $this->reservedMatterCatalog,
            'governing_partners' => $this->governingPartners,
            'meeting_records' => $this->meetings,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'governing_partners' => count($this->governingPartners),
                'governance_weight' => array_sum(array_column($this->governingPartners, 'governance_weight')),
                'reserved_matters' => count($this->reservedMatterCatalog),
                'meetings' => count($this->meetings),
                'decision_record_candidates' => array_sum(array_column($this->meetings, 'decision_record_candidate_count')),
                'by_lifecycle' => $this->lifecycleCounts,
                'by_outcome' => $this->outcomeCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'meeting_gaps' => $this->meetingGaps,
                'authority_gaps' => $this->authorityGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'Governance weight is derived from Partnership Formation and is never copied into a Meeting Record.',
                'Attendance, quorum, conflict disclosure, recusal, vote, outcome, execution, and Evidence remain separate facts.',
                'Silence, attendance, abstention, or later execution never implies affirmative consent.',
                'A recused Partner does not vote on the affected agenda item, but recusal does not erase the historical attendance record.',
                'No outcome is inferred while quorum, approval threshold, or applicable deadlock mechanics remain unresolved.',
            ],
        ];
    }
}
