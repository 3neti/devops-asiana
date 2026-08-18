<?php

namespace App\Continuity;

final readonly class ResolvedContinuityExercises
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $recordRequirements
     * @param  list<array<string, mixed>>  $exerciseRecords
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
        public array $exerciseRecords,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $conflicts,
        public array $decisionGaps,
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
                $this->decisionGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policies' => $this->governingPolicies,
            'record_requirements' => $this->recordRequirements,
            'exercise_records' => $this->exerciseRecords,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'exercise_records' => count($this->exerciseRecords),
                'evidence_records' => count($this->evidenceRecords),
                'objectives_missed' => array_sum(array_map(static fn (array $record): int => (int) ($record['objectives_missed'] ?? 0), $this->exerciseRecords)),
                'unresolved_gaps' => array_sum(array_map(static fn (array $record): int => (int) ($record['unresolved_gaps'] ?? 0), $this->exerciseRecords)),
                'ready_for_closure' => count(array_filter($this->exerciseRecords, static fn (array $record): bool => ($record['may_close_exercise'] ?? false) === true)),
                'by_lifecycle_status' => $this->lifecycleCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No generic Client RTO or RPO is implied; every objective is explicit, approved, and sourced.',
                'Backup success does not prove restorability.',
                'Observed recovery time and recovery-point age are facts, not assumptions.',
                'Exercise execution and independent verification remain separate.',
                'A failed exercise is useful evidence and must produce accountable gap disposition.',
                'Closure requires verified results, controlled test data, linked corrective action, and separate authority.',
            ],
        ];
    }
}
