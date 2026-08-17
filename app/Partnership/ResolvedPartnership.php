<?php

namespace App\Partnership;

final readonly class ResolvedPartnership
{
    /**
     * @param  array<string, mixed>  $formation
     * @param  array<string, mixed>  $constitution
     * @param  list<InstitutionalDecision>  $decisions
     * @param  array<string, mixed>  $projections
     * @param  list<array{code: string, status: string, message: string}>  $consistencyChecks
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array<string, mixed>>  $decisionGaps
     * @param  list<array<string, mixed>>  $counselReview
     * @param  list<array{key: string, label: string, type: string}>  $responsibilityGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $formation,
        public array $constitution,
        public array $decisions,
        public array $projections,
        public array $consistencyChecks,
        public array $conflicts,
        public array $decisionGaps,
        public array $counselReview,
        public array $responsibilityGaps,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $hasFailedConsistencyCheck = in_array(
            'failed',
            array_column($this->consistencyChecks, 'status'),
            true,
        );

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts === [] && ! $hasFailedConsistencyCheck
                ? 'consistent_with_open_decisions'
                : 'conflict_detected',
            'formation' => $this->formation,
            'constitution' => $this->constitution,
            'decisions' => array_map(
                static fn (InstitutionalDecision $decision): array => $decision->toArray(),
                $this->decisions,
            ),
            'projections' => $this->projections,
            'reports' => [
                'consistency' => $this->consistencyChecks,
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'counsel_review' => $this->counselReview,
                'responsibility_gaps' => $this->responsibilityGaps,
            ],
            'disclaimer' => 'This projection expresses institutional intent. It is not legal advice or a representation that a Partnership Agreement is legally valid.',
        ];
    }
}
