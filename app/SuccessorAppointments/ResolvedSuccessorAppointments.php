<?php

namespace App\SuccessorAppointments;

final readonly class ResolvedSuccessorAppointments
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $appointmentRecords
     * @param  list<array<string, mixed>>  $assignmentAdmissions
     * @param  list<array<string, mixed>>  $activationAdmissions
     * @param  array<string, list<string>>  $coverageHolderOverrides
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $appointmentGaps
     * @param  list<array{code: string, message: string}>  $approvalGaps
     * @param  list<array{code: string, message: string}>  $acceptanceGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $appointmentRecords,
        public array $assignmentAdmissions,
        public array $activationAdmissions,
        public array $coverageHolderOverrides,
        public array $evidenceRecords,
        public array $conflicts,
        public array $appointmentGaps,
        public array $approvalGaps,
        public array $acceptanceGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->appointmentGaps !== [] || $this->approvalGaps !== [] || $this->acceptanceGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'appointment_records' => $this->appointmentRecords,
            'assignment_admissions' => $this->assignmentAdmissions,
            'activation_admissions' => $this->activationAdmissions,
            'coverage_holder_overrides' => $this->coverageHolderOverrides,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'appointment_records' => count($this->appointmentRecords),
                'assignment_admissions' => count($this->assignmentAdmissions),
                'activation_admissions' => count($this->activationAdmissions),
                'coverage_overrides' => count($this->coverageHolderOverrides),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'appointment_gaps' => $this->appointmentGaps,
                'approval_gaps' => $this->approvalGaps,
                'acceptance_gaps' => $this->acceptanceGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A successor is a new exact Role Assignment, never a renamed predecessor.',
                'A vacancy closes only through a separately evidenced appointment and activation.',
                'The successor must satisfy the Role qualification independently of the outgoing holder.',
                'Appointment approval, holder acceptance, activation, and Evidence remain separate facts.',
                'A successor admission grants no Firm Authority; an Office remains bounded by the Authority Matrix.',
                'Capital, governance, compensation, and Partner status are never inferred from appointment.',
            ],
        ];
    }
}
