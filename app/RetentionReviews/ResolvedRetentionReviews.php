<?php

namespace App\RetentionReviews;

final readonly class ResolvedRetentionReviews
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $reviews
     * @param  list<array<string, mixed>>  $resolvedReviews
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $reviewGaps
     * @param  list<array{code: string, message: string}>  $exceptionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $reviews,
        public array $resolvedReviews,
        public array $conflicts,
        public array $reviewGaps,
        public array $exceptionGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->reviewGaps !== [] || $this->exceptionGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'reviews' => $this->reviews,
            'resolved_reviews' => $this->resolvedReviews,
            'counts' => [
                'reviews' => count($this->reviews),
                'resolved_reviews' => count($this->resolvedReviews),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'review_gaps' => $this->reviewGaps,
                'exception_gaps' => $this->exceptionGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A retention review does not silently extend or waive a policy.',
                'Policy Exceptions target an exact policy version and have their own approval and expiry.',
                'A review outcome remains separate from custody, disposition, and corrective action.',
                'Review evidence must be attributable and must reference indexed Evidence.',
            ],
        ];
    }
}
