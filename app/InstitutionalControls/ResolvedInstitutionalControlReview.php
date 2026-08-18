<?php

namespace App\InstitutionalControls;

final readonly class ResolvedInstitutionalControlReview
{
    /**
     * @param  list<array<string, string>>  $controls
     * @param  list<array<string, mixed>>  $controlReviews
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    public function __construct(
        public int $schemaVersion,
        public array $controls,
        public array $controlReviews,
        public array $conflicts,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $attention = count(array_filter(
            $this->controlReviews,
            static fn (array $review): bool => $review['status'] === 'attention_required',
        ));

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($attention > 0 ? 'attention_required' : 'consistent'),
            'controls' => $this->controls,
            'control_reviews' => $this->controlReviews,
            'counts' => [
                'controls' => count($this->controls),
                'attention_required' => $attention,
                'consistent' => count($this->controlReviews) - $attention,
            ],
            'reports' => ['conflicts' => $this->conflicts],
            'principles' => [
                'This is a read-only cross-domain review projection, not a replacement for source compilers.',
                'A summarized gap remains owned by its source domain and must be resolved there.',
                'The review never grants authority, accepts risk, creates exceptions, or closes remediation.',
                'Source histories and evidence remain canonical outside this projection.',
            ],
        ];
    }
}
