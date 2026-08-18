<?php

namespace App\EvidenceCustody;

final readonly class ResolvedEvidenceCustody
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $records
     * @param  list<array<string, mixed>>  $resolvedRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $sourceGaps
     * @param  list<array{code: string, message: string}>  $custodyGaps
     * @param  list<array{code: string, message: string}>  $retentionGaps
     * @param  list<array{code: string, message: string}>  $integrityGaps
     * @param  list<array{code: string, message: string}>  $dispositionGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $records,
        public array $resolvedRecords,
        public array $conflicts,
        public array $sourceGaps,
        public array $custodyGaps,
        public array $retentionGaps,
        public array $integrityGaps,
        public array $dispositionGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->sourceGaps !== []
            || $this->custodyGaps !== []
            || $this->retentionGaps !== []
            || $this->integrityGaps !== []
            || $this->dispositionGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'records' => $this->records,
            'resolved_records' => $this->resolvedRecords,
            'counts' => [
                'records' => count($this->records),
                'resolved_records' => count($this->resolvedRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'source_gaps' => $this->sourceGaps,
                'custody_gaps' => $this->custodyGaps,
                'retention_gaps' => $this->retentionGaps,
                'integrity_gaps' => $this->integrityGaps,
                'disposition_gaps' => $this->dispositionGaps,
            ],
            'principles' => [
                'Custody, retention, integrity, and disposition remain separate Evidence facts.',
                'Evidence disposition never erases the institutional index.',
                'Retention is explicit and reviewable; no retention period is inferred.',
                'The compiler does not store Evidence payloads or secrets.',
            ],
        ];
    }
}
