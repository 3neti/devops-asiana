<?php

namespace App\ControlHistory;

final readonly class ResolvedInstitutionalControlHistoryVerificationLinkReconciliations
{
    /**
     * @param  list<array<string, mixed>>  $reconciliations
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $reconciliationGaps
     */
    public function __construct(
        public int $schemaVersion,
        public string $reconciliationKey,
        public string $source,
        public array $reconciliations,
        public array $conflicts,
        public array $reconciliationGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $status = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->reconciliations === [] => 'not_supplied',
            $this->reconciliationGaps !== [] => 'consistent_with_gaps',
            default => 'consistent',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'reconciliation_key' => $this->reconciliationKey,
            'source' => $this->source,
            'status' => $status,
            'reconciliations' => $this->reconciliations,
            'counts' => [
                'reconciliations' => count($this->reconciliations),
                'conflicts' => count($this->conflicts),
                'reconciliation_gaps' => count($this->reconciliationGaps),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'reconciliation_gaps' => $this->reconciliationGaps,
            ],
            'principles' => [
                'Reconciliation compares explicit records with source projections; it never rewrites either source.',
                'Snapshot drift remains a discrepancy rather than an automatic correction.',
                'Reconciliation Evidence is referenced separately and payloads remain outside this boundary.',
                'A reconciliation does not admit Evidence, grant authority, accept risk, or close remediation.',
            ],
        ];
    }
}
