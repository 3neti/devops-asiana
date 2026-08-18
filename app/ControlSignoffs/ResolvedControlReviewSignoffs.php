<?php

namespace App\ControlSignoffs;

final readonly class ResolvedControlReviewSignoffs
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $signoffs
     * @param  list<array<string, mixed>>  $resolvedSignoffs
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $reviewGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $signoffs,
        public array $resolvedSignoffs,
        public array $conflicts,
        public array $reviewGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->reviewGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'signoffs' => $this->signoffs,
            'resolved_signoffs' => $this->resolvedSignoffs,
            'counts' => [
                'signoffs' => count($this->signoffs),
                'resolved_signoffs' => count($this->resolvedSignoffs),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'review_gaps' => $this->reviewGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A sign-off records independent review of an export; it is not approval or risk acceptance.',
                'A sign-off never creates a Policy Exception or closes Corrective Action.',
                'The reviewed export identity and status are snapshotted explicitly.',
                'Sign-off Evidence remains a separate attributable record.',
            ],
        ];
    }
}
