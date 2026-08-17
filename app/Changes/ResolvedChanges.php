<?php

namespace App\Changes;

final readonly class ResolvedChanges
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $recordRequirements
     * @param  list<array<string, mixed>>  $changeRecords
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
        public array $recordRequirements,
        public array $changeRecords,
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
            'record_requirements' => $this->recordRequirements,
            'change_records' => $this->changeRecords,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'change_records' => count($this->changeRecords),
                'evidence_records' => count($this->evidenceRecords),
                'by_lifecycle_status' => $this->lifecycleCounts,
                'executable_authority' => count(array_filter(
                    $this->changeRecords,
                    static fn (array $change): bool => ($change['may_execute_change'] ?? false) === true,
                )),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No Ticket, No Change.',
                'No Production Change Without Recovery.',
                'An Access Grant permits access use; it does not authorize a specific Change.',
                'Approval, scheduling, execution, verification, and closure remain separate records.',
                'Deployment never implies approval.',
                'Emergency procedure accelerates authority; it does not erase evidence or review.',
            ],
        ];
    }
}
