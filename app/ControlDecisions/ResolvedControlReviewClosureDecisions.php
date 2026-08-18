<?php

namespace App\ControlDecisions;

final readonly class ResolvedControlReviewClosureDecisions
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $decisions
     * @param  list<array<string, mixed>>  $resolvedDecisions
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $decisions,
        public array $resolvedDecisions,
        public array $conflicts,
        public array $decisionGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->decisionGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'decisions' => $this->decisions,
            'resolved_decisions' => $this->resolvedDecisions,
            'counts' => [
                'decisions' => count($this->decisions),
                'resolved_decisions' => count($this->resolvedDecisions),
                'closure_admissions' => count(array_filter($this->resolvedDecisions, static fn (array $decision): bool => $decision['closure_admitted'] === true)),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Closure is a separate authorized decision after eligibility; eligibility never closes an action.',
                'A closure decision records institutional truth but does not rewrite source Action history.',
                'Decision, authority basis, reason, and Evidence remain explicit.',
                'Deferred or rejected decisions do not imply closure.',
            ],
        ];
    }
}
