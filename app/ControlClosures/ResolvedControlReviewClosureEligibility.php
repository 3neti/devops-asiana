<?php

namespace App\ControlClosures;

final readonly class ResolvedControlReviewClosureEligibility
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $reviews
     * @param  list<array<string, mixed>>  $eligibilityReviews
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $eligibilityGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $reviews,
        public array $eligibilityReviews,
        public array $conflicts,
        public array $eligibilityGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->eligibilityGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'reviews' => $this->reviews,
            'eligibility_reviews' => $this->eligibilityReviews,
            'counts' => [
                'reviews' => count($this->reviews),
                'eligible' => count(array_filter($this->eligibilityReviews, static fn (array $review): bool => $review['closure_eligible'] === true)),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'eligibility_gaps' => $this->eligibilityGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Eligibility is a prerequisite report, not a closure decision.',
                'Completion claims and independent verification remain separate outcomes.',
                'Closure authority and closure Evidence are explicit and separate.',
                'No action is closed, erased, or mutated by this compiler.',
            ],
        ];
    }
}
