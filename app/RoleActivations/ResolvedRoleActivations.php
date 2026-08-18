<?php

namespace App\RoleActivations;

final readonly class ResolvedRoleActivations
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $candidates
     * @param  list<array<string, mixed>>  $activationRecords
     * @param  list<array<string, mixed>>  $assignmentActivationAdmissions
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $activationGaps
     * @param  list<array{code: string, message: string}>  $acceptanceGaps
     * @param  list<array{code: string, message: string}>  $verificationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $candidates,
        public array $activationRecords,
        public array $assignmentActivationAdmissions,
        public array $evidenceRecords,
        public array $conflicts,
        public array $activationGaps,
        public array $acceptanceGaps,
        public array $verificationGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->activationGaps !== [], $this->acceptanceGaps !== [], $this->verificationGaps !== [], $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'candidates' => $this->candidates,
            'activation_records' => $this->activationRecords,
            'assignment_activation_admissions' => $this->assignmentActivationAdmissions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'candidate_assignments' => count($this->candidates),
                'commencement_eligible_assignments' => count(array_filter(
                    $this->candidates,
                    static fn (array $candidate): bool => ($candidate['commencement_eligible'] ?? false) === true,
                )),
                'recorded_assumptions' => count($this->activationRecords),
                'admitted_activations' => count($this->assignmentActivationAdmissions),
                'pending_assignments' => count($this->candidates) - count($this->assignmentActivationAdmissions),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'activation_gaps' => $this->activationGaps,
                'acceptance_gaps' => $this->acceptanceGaps,
                'verification_gaps' => $this->verificationGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Firm Commencement makes a formation-derived assignment eligible; it never activates the assignment by itself.',
                'A holder assumes one exact Office or professional responsibility through an attributable acceptance record.',
                'Independent verification is separate from holder acceptance and activation recording.',
                'Activating one assignment never activates another assignment held by the same person.',
                'Professional responsibility does not create Firm Authority; authority remains a separate resolution.',
                'Every material activation fact links to assignment-specific Evidence.',
            ],
        ];
    }
}
