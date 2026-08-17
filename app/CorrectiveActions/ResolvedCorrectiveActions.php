<?php

namespace App\CorrectiveActions;

final readonly class ResolvedCorrectiveActions
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $recordRequirements
     * @param  list<array<string, mixed>>  $correctiveActions
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
        public array $correctiveActions,
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
            'corrective_actions' => $this->correctiveActions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'corrective_actions' => count($this->correctiveActions),
                'evidence_records' => count($this->evidenceRecords),
                'overdue' => count(array_filter($this->correctiveActions, static fn (array $action): bool => ($action['overdue'] ?? false) === true)),
                'awaiting_verification' => count(array_filter($this->correctiveActions, static fn (array $action): bool => ($action['operational_status'] ?? null) === 'awaiting_verification')),
                'ready_for_closure' => count(array_filter($this->correctiveActions, static fn (array $action): bool => ($action['may_close_corrective_action'] ?? false) === true)),
                'by_lifecycle_status' => $this->lifecycleCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'A source record may close; its corrective action remains independently accountable.',
                'Every accepted corrective action has exactly one accountable owner and one current due date.',
                'Due-date changes are explicit, authorized, reasoned, evidenced, and historically preserved.',
                'An owner may claim completion but may not independently verify their own work.',
                'Verification does not imply closure; closure is a separate authorized decision.',
                'Overdue work is visible and requires explicit escalation.',
            ],
            'boundary' => 'This is an institutional remediation register, not a generic task or project-management system.',
        ];
    }
}
