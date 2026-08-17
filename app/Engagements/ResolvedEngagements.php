<?php

namespace App\Engagements;

final readonly class ResolvedEngagements
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $openingRequirements
     * @param  list<array<string, mixed>>  $engagements
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
        public array $openingRequirements,
        public array $engagements,
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
            'opening_requirements' => $this->openingRequirements,
            'engagements' => $this->engagements,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'engagements' => count($this->engagements),
                'evidence_records' => count($this->evidenceRecords),
                'by_lifecycle_status' => $this->lifecycleCounts,
                'open_for_client_work' => count(array_filter(
                    $this->engagements,
                    static fn (array $engagement): bool => ($engagement['may_perform_client_work'] ?? false) === true,
                )),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No Client Work Without Engagement.',
                'Every material Engagement has exactly one Responsible Partner.',
                'Client Mandate, Firm Authority, and Specific Approval remain separate.',
                'Approval does not imply opening, and opening does not infer approval.',
                'Everything material leaves evidence.',
            ],
        ];
    }
}
