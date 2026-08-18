<?php

namespace App\IdentityAndRoles;

final readonly class ResolvedIdentityAndRoles
{
    /**
     * @param  list<array<string, mixed>>  $identities
     * @param  list<array<string, mixed>>  $roles
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $assignmentLifecycleCounts
     * @param  array<string, int>  $roleCoverageCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $identityGaps
     * @param  list<array{code: string, message: string}>  $activationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $holderMismatches
     */
    public function __construct(
        public int $schemaVersion,
        public array $identities,
        public array $roles,
        public array $assignments,
        public array $evidenceRecords,
        public array $assignmentLifecycleCounts,
        public array $roleCoverageCounts,
        public array $conflicts,
        public array $identityGaps,
        public array $activationGaps,
        public array $evidenceGaps,
        public array $holderMismatches,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [], $this->holderMismatches !== [] => 'conflict_detected',
                $this->identityGaps !== [], $this->activationGaps !== [], $this->evidenceGaps !== [], $this->roleCoverageCounts['vacant'] > 0 => 'consistent_with_gaps',
                default => 'consistent',
            },
            'identities' => $this->identities,
            'roles' => $this->roles,
            'assignments' => $this->assignments,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'identities' => count($this->identities),
                'roles' => count($this->roles),
                'assignments' => count($this->assignments),
                'authority_effective' => count(array_filter(
                    $this->assignments,
                    static fn (array $assignment): bool => ($assignment['grants_firm_authority'] ?? false) === true,
                )),
                'authentication_bindings' => count(array_filter(
                    $this->identities,
                    static fn (array $identity): bool => ($identity['authentication_binding'] ?? null) !== null,
                )),
                'by_assignment_lifecycle' => $this->assignmentLifecycleCounts,
                'by_role_coverage' => $this->roleCoverageCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'identity_gaps' => $this->identityGaps,
                'activation_gaps' => $this->activationGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'holder_mismatches' => $this->holderMismatches,
            ],
            'principles' => [
                'Institutional identity is not an application login, employee record, Partner status, or system account.',
                'Partner status, office appointment, professional responsibility, and delegated authority are separate records.',
                'Approval does not activate an assignment; effective time, evidence, and lifecycle remain explicit.',
                'A professional role carries responsibility but does not bind the Firm unless a separate authority source says so.',
                'Ending an assignment does not erase its history or silently transfer it to another person.',
            ],
        ];
    }
}
