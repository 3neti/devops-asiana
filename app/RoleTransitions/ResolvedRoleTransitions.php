<?php

namespace App\RoleTransitions;

final readonly class ResolvedRoleTransitions
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $transitionRecords
     * @param  list<array<string, mixed>>  $assignmentTransitionAdmissions
     * @param  list<array<string, mixed>>  $scheduledTransitions
     * @param  list<array<string, mixed>>  $vacancies
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $transitionGaps
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $transitionRecords,
        public array $assignmentTransitionAdmissions,
        public array $scheduledTransitions,
        public array $vacancies,
        public array $evidenceRecords,
        public array $conflicts,
        public array $transitionGaps,
        public array $decisionGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->transitionGaps !== [] || $this->decisionGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'transition_records' => $this->transitionRecords,
            'assignment_transition_admissions' => $this->assignmentTransitionAdmissions,
            'scheduled_transitions' => $this->scheduledTransitions,
            'vacancies' => $this->vacancies,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'transition_records' => count($this->transitionRecords),
                'effective_transitions' => count($this->assignmentTransitionAdmissions),
                'scheduled_transitions' => count($this->scheduledTransitions),
                'vacancies' => count($this->vacancies),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'transition_gaps' => $this->transitionGaps,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A transition changes one exact assignment; it never rewrites or deletes its history.',
                'Suspension disables operation without creating a new holder or transferring authority.',
                'Resignation, removal, revocation, and ending create a visible vacancy unless a separately valid successor exists.',
                'A successor is never inferred from relationship, title, or proximity to the outgoing holder.',
                'A transition admission does not grant Firm Authority; it only changes the bounded lifecycle of the target assignment.',
                'Decision, effective transition, verification, and Evidence remain separate facts.',
            ],
        ];
    }
}
