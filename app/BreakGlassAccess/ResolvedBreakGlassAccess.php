<?php

namespace App\BreakGlassAccess;

final readonly class ResolvedBreakGlassAccess
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $recordRequirements
     * @param  list<array<string, mixed>>  $accessRecords
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
        public array $accessRecords,
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
            'access_records' => $this->accessRecords,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'access_records' => count($this->accessRecords),
                'evidence_records' => count($this->evidenceRecords),
                'active_emergency_authority' => count(array_filter(
                    $this->accessRecords,
                    static fn (array $record): bool => ($record['may_use_break_glass'] ?? false) === true,
                )),
                'awaiting_review' => count(array_filter(
                    $this->accessRecords,
                    static fn (array $record): bool => ($record['operational_status'] ?? null) === 'awaiting_review',
                )),
                'by_lifecycle_status' => $this->lifecycleCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'Break-glass is a separate emergency path, never an ordinary Access Grant.',
                'Credential possession does not create authority.',
                'Emergency authority is named, independently approved, scoped, logged, and time-bounded.',
                'Expiry ends authority even when technical revocation has not yet been verified.',
                'Every use is disclosed and independently reviewed.',
                'Emergency access never silently becomes standing access.',
            ],
            'prohibited_content' => 'Passwords, tokens, private keys, recovery codes, and credential values must never appear in canonical records.',
        ];
    }
}
