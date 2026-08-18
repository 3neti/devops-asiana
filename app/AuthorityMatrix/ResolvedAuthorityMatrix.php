<?php

namespace App\AuthorityMatrix;

final readonly class ResolvedAuthorityMatrix
{
    /**
     * @param  array<string, mixed>  $governingPolicy
     * @param  list<array<string, mixed>>  $domains
     * @param  list<array<string, mixed>>  $entries
     * @param  list<array<string, string>>  $deferredDecisions
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $lifecycleCounts
     * @param  array<string, int>  $resolutionCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $sourceGaps
     * @param  list<array{code: string, message: string}>  $holderGaps
     * @param  list<array{code: string, message: string}>  $boundaryGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicy,
        public array $domains,
        public array $entries,
        public array $deferredDecisions,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $resolutionCounts,
        public array $conflicts,
        public array $sourceGaps,
        public array $holderGaps,
        public array $boundaryGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->sourceGaps !== [], $this->holderGaps !== [], $this->boundaryGaps !== [], $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policy' => $this->governingPolicy,
            'domains' => $this->domains,
            'entries' => $this->entries,
            'deferred_decisions' => $this->deferredDecisions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'domains' => count($this->domains),
                'actions' => array_sum(array_map(
                    static fn (array $domain): int => count($domain['actions'] ?? []),
                    $this->domains,
                )),
                'entries' => count($this->entries),
                'deferred_decisions' => count($this->deferredDecisions),
                'effective_entries' => $this->resolutionCounts['effective'],
                'effective_holders' => array_sum(array_map(
                    static fn (array $entry): int => count($entry['effective_holder_keys'] ?? []),
                    $this->entries,
                )),
                'by_lifecycle' => $this->lifecycleCounts,
                'by_resolution' => $this->resolutionCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'source_gaps' => $this->sourceGaps,
                'holder_gaps' => $this->holderGaps,
                'boundary_gaps' => $this->boundaryGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'Firm Authority identifies who may decide or approve; it does not supply Client Mandate or Specific Approval.',
                'Identity, Partner status, office, professional responsibility, authentication, technical access, and authority remain separate facts.',
                'Authority must resolve through an operative constitutional status, office assignment, or bounded delegation.',
                'Draft policy and Design entries describe future controls but create no operative authority.',
                'Approval, execution, verification, and evidence remain distinct; execution never proves authorization.',
            ],
        ];
    }
}
