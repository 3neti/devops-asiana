<?php

namespace App\Policies;

final readonly class ResolvedPolicyRegistry
{
    /**
     * @param  list<array<string, mixed>>  $policies
     * @param  list<array<string, mixed>>  $approvalAdmissions
     * @param  list<array<string, mixed>>  $publicationRecords
     * @param  list<array<string, mixed>>  $activationRecords
     * @param  list<array<string, mixed>>  $availableDecisionCandidates
     * @param  list<array<string, mixed>>  $exceptions
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $statusCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $lifecycleGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $admissionGaps
     * @param  list<array{code: string, message: string}>  $activationGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $policies,
        public array $exceptions,
        public array $evidenceRecords,
        public array $statusCounts,
        public array $conflicts,
        public array $lifecycleGaps,
        public array $evidenceGaps,
        public array $approvalAdmissions = [],
        public array $publicationRecords = [],
        public array $activationRecords = [],
        public array $admissionGaps = [],
        public array $activationGaps = [],
        public array $availableDecisionCandidates = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $compilerStatus = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->lifecycleGaps !== [], $this->evidenceGaps !== [], $this->admissionGaps !== [], $this->activationGaps !== [] => 'consistent_with_gaps',
            default => 'consistent',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $compilerStatus,
            'policies' => $this->policies,
            'policy_approval_admission_records' => $this->approvalAdmissions,
            'policy_publication_records' => $this->publicationRecords,
            'policy_activation_records' => $this->activationRecords,
            'available_policy_decision_candidates' => $this->availableDecisionCandidates,
            'exceptions' => $this->exceptions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'policies' => count($this->policies),
                'approval_admissions' => count(array_filter($this->approvalAdmissions, static fn (array $record): bool => $record['grants_policy_approval_basis'] === true)),
                'publications' => count(array_filter($this->publicationRecords, static fn (array $record): bool => $record['publication_verified'] === true)),
                'activations' => count(array_filter($this->activationRecords, static fn (array $record): bool => $record['activation_verified'] === true)),
                'available_decision_candidates' => count($this->availableDecisionCandidates),
                'exceptions' => count($this->exceptions),
                'evidence_records' => count($this->evidenceRecords),
                'by_status' => $this->statusCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'lifecycle_gaps' => $this->lifecycleGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'admission_gaps' => $this->admissionGaps,
                'activation_gaps' => $this->activationGaps,
            ],
            'principles' => [
                'Approval is an explicit record; publication or use never implies approval.',
                'An effective Decision Record may support approval only through an exact Policy Approval Admission Record.',
                'Approval, publication, effective date, and activation Evidence remain separate facts.',
                'Policy content becomes immutable when submitted for review.',
                'An exception is scoped to an exact policy version and requirement.',
                'Exceptions are temporary, approved, evidenced, and reviewable.',
            ],
        ];
    }
}
