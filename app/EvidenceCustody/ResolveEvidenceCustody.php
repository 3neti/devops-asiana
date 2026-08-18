<?php

namespace App\EvidenceCustody;

use App\EvidenceIndex\ResolvedEvidenceIndex;

final class ResolveEvidenceCustody
{
    public function handle(EvidenceCustodyDefinition $definition, ResolvedEvidenceIndex $index): ResolvedEvidenceCustody
    {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $sourceGaps */
        $sourceGaps = [];
        /** @var list<array{code: string, message: string}> $custodyGaps */
        $custodyGaps = [];
        /** @var list<array{code: string, message: string}> $retentionGaps */
        $retentionGaps = [];
        /** @var list<array{code: string, message: string}> $integrityGaps */
        $integrityGaps = [];
        /** @var list<array{code: string, message: string}> $dispositionGaps */
        $dispositionGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $indexedEvidence = $this->evidenceKeys($index);

        foreach ($definition->records as $record) {
            $key = (string) ($record['key'] ?? '');
            $issuesBefore = $this->issueCount($conflicts, $sourceGaps, $custodyGaps, $retentionGaps, $integrityGaps, $dispositionGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_custody_key', 'Evidence Custody has a missing or duplicate key.');
            }
            $keys[] = $key;
            $evidenceKey = (string) ($record['evidence_key'] ?? '');
            if (! isset($indexedEvidence[$evidenceKey])) {
                $sourceGaps[] = $this->issue('custody_evidence_not_indexed', "Custody record {$key} references Evidence not present in the Index.");
            }
            if (empty($record['source_system']) || empty($record['source_reference']) || empty($record['custodian'])) {
                $sourceGaps[] = $this->issue('custody_source_incomplete', "Custody record {$key} lacks source and custodian facts.");
            }
            $events = is_array($record['custody_events'] ?? null) ? $record['custody_events'] : [];
            if ($events === []) {
                $custodyGaps[] = $this->issue('custody_history_missing', "Custody record {$key} has no custody history.");
            }
            $retention = is_array($record['retention'] ?? null) ? $record['retention'] : [];
            if (empty($retention['policy_reference']) || empty($retention['retain_until']) || empty($retention['review_at'])) {
                $retentionGaps[] = $this->issue('retention_basis_incomplete', "Custody record {$key} lacks explicit retention and review dates.");
            }
            $integrity = is_array($record['integrity'] ?? null) ? $record['integrity'] : [];
            if (empty($integrity['algorithm']) || empty($integrity['digest']) || empty($integrity['verified_at']) || empty($integrity['verified_by'])) {
                $integrityGaps[] = $this->issue('integrity_basis_incomplete', "Custody record {$key} lacks integrity verification facts.");
            }
            $disposition = is_array($record['disposition'] ?? null) ? $record['disposition'] : [];
            if (empty($disposition['status']) || ! in_array($disposition['status'], ['retained', 'disposed', 'superseded'], true)) {
                $dispositionGaps[] = $this->issue('disposition_incomplete', "Custody record {$key} lacks a valid disposition.");
            }
            if ($issuesBefore === $this->issueCount($conflicts, $sourceGaps, $custodyGaps, $retentionGaps, $integrityGaps, $dispositionGaps)) {
                $resolved[] = [...$record, 'custody_resolved' => true];
            }
        }

        return new ResolvedEvidenceCustody(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            records: $definition->records,
            resolvedRecords: $resolved,
            conflicts: $conflicts,
            sourceGaps: $sourceGaps,
            custodyGaps: $custodyGaps,
            retentionGaps: $retentionGaps,
            integrityGaps: $integrityGaps,
            dispositionGaps: $dispositionGaps,
        );
    }

    /** @return array<string, true> */
    private function evidenceKeys(ResolvedEvidenceIndex $index): array
    {
        $keys = [];

        foreach ($index->evidenceRecords as $record) {
            if (is_string($record['key'] ?? null)) {
                $keys[$record['key']] = true;
            }
        }

        return $keys;
    }

    /** @param list<array{code: string, message: string}> ...$reports */
    private function issueCount(array ...$reports): int
    {
        return array_sum(array_map(count(...), $reports));
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
