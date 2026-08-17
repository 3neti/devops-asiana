<?php

namespace App\ClientAcceptance;

final readonly class ResolvedClientAcceptance
{
    /**
     * @param  array<string, mixed>  $governingPolicy
     * @param  list<array<string, mixed>>  $requiredAssessments
     * @param  list<array<string, mixed>>  $prospectiveClients
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $reviewCounts
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $readinessGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicy,
        public array $requiredAssessments,
        public array $prospectiveClients,
        public array $evidenceRecords,
        public array $reviewCounts,
        public array $outcomeCounts,
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
        $compilerStatus = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->decisionGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
            default => 'consistent',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $compilerStatus,
            'governing_policy' => $this->governingPolicy,
            'required_assessments' => $this->requiredAssessments,
            'prospective_clients' => $this->prospectiveClients,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'prospective_clients' => count($this->prospectiveClients),
                'evidence_records' => count($this->evidenceRecords),
                'by_review_status' => $this->reviewCounts,
                'by_outcome' => $this->outcomeCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No Client Without Acceptance.',
                'Acceptance is an explicit decision and cannot be inferred from work, access, or an Engagement.',
                'Acceptance permits Engagement consideration; it does not authorize Client work.',
                'Conflicts, related parties, risks, conditions, authority, and evidence remain visible.',
            ],
        ];
    }
}
