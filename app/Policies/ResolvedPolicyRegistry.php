<?php

namespace App\Policies;

final readonly class ResolvedPolicyRegistry
{
    /**
     * @param  list<array<string, mixed>>  $policies
     * @param  list<array<string, mixed>>  $exceptions
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $statusCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $lifecycleGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
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
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $compilerStatus = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->lifecycleGaps !== [], $this->evidenceGaps !== [] => 'consistent_with_gaps',
            default => 'consistent',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $compilerStatus,
            'policies' => $this->policies,
            'exceptions' => $this->exceptions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'policies' => count($this->policies),
                'exceptions' => count($this->exceptions),
                'evidence_records' => count($this->evidenceRecords),
                'by_status' => $this->statusCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'lifecycle_gaps' => $this->lifecycleGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Approval is an explicit record; publication or use never implies approval.',
                'Policy content becomes immutable when submitted for review.',
                'An exception is scoped to an exact policy version and requirement.',
                'Exceptions are temporary, approved, evidenced, and reviewable.',
            ],
        ];
    }
}
