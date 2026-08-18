<?php

namespace App\ControlOutcomes;

final readonly class ResolvedControlReviewActionOutcomes
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $outcomes
     * @param  list<array<string, mixed>>  $resolvedOutcomes
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $outcomeGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $outcomes,
        public array $resolvedOutcomes,
        public array $conflicts,
        public array $outcomeGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->outcomeGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'outcomes' => $this->outcomes,
            'resolved_outcomes' => $this->resolvedOutcomes,
            'counts' => [
                'outcomes' => count($this->outcomes),
                'resolved_outcomes' => count($this->resolvedOutcomes),
                'completion_claims' => count(array_filter($this->resolvedOutcomes, static fn (array $outcome): bool => $outcome['outcome_type'] === 'completion_claim')),
                'verification_references' => count(array_filter($this->resolvedOutcomes, static fn (array $outcome): bool => $outcome['outcome_type'] === 'verification_reference')),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'outcome_gaps' => $this->outcomeGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Progress and verification references preserve action history but never imply completion or closure.',
                'A completion claim is not independent verification.',
                'Verification reference is not a closure authorization.',
                'Evidence remains explicit for every material outcome.',
            ],
        ];
    }
}
