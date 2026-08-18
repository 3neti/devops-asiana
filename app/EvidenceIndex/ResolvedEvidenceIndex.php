<?php

namespace App\EvidenceIndex;

final readonly class ResolvedEvidenceIndex
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $records
     * @param  list<array<string, mixed>>  $indexedRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $pathGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(public int $schemaVersion, public array $requirements, public array $records, public array $indexedRecords, public array $evidenceRecords, public array $conflicts, public array $pathGaps, public array $evidenceGaps) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $byArtifact = [];
        foreach ($this->indexedRecords as $record) {
            $byArtifact[$record['artifact_type']] = ($byArtifact[$record['artifact_type']] ?? 0) + 1;
        }

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected', $this->pathGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps', default => 'consistent'
            },
            'requirements' => $this->requirements,
            'records' => $this->records,
            'indexed_records' => $this->indexedRecords,
            'evidence_records' => $this->evidenceRecords,
            'counts' => ['records' => count($this->records), 'indexed_records' => count($this->indexedRecords), 'evidence_records' => count($this->evidenceRecords), 'by_artifact_type' => $byArtifact],
            'reports' => ['conflicts' => $this->conflicts, 'path_gaps' => $this->pathGaps, 'evidence_gaps' => $this->evidenceGaps],
            'principles' => ['The index is a traceability projection, not a source of authority.', 'Every indexed artifact retains its Client, Engagement, Matter, and Evidence references.', 'Missing path or Evidence links remain visible and are never inferred.', 'The index does not become a generic search or workflow engine.'],
        ];
    }
}
