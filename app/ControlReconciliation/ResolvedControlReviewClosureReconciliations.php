<?php

namespace App\ControlReconciliation;

final readonly class ResolvedControlReviewClosureReconciliations
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $reconciliations
     * @param  list<array<string, mixed>>  $resolvedReconciliations
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $reconciliationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $reconciliations,
        public array $resolvedReconciliations,
        public array $conflicts,
        public array $reconciliationGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->reconciliationGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'reconciliations' => $this->reconciliations,
            'resolved_reconciliations' => $this->resolvedReconciliations,
            'counts' => [
                'reconciliations' => count($this->reconciliations),
                'resolved_reconciliations' => count($this->resolvedReconciliations),
                'discrepancies' => count(array_filter($this->resolvedReconciliations, static fn (array $record): bool => ($record['reconciled'] ?? false) === false)),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'reconciliation_gaps' => $this->reconciliationGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Reconciliation compares sources; it does not rewrite either source.',
                'A closure decision and downstream action state remain separate facts.',
                'Discrepancies are explicit findings, not automatic corrections.',
                'Reconciliation Evidence is attributable and separate.',
            ],
        ];
    }
}
