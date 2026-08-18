<?php

namespace App\EvidenceIndex;

final class ResolveEvidenceIndex
{
    public function handle(EvidenceIndexDefinition $definition): ResolvedEvidenceIndex
    {
        /** @var list<array{code: string, message: string}> $conflicts */ $conflicts = [];
        /** @var list<array{code: string, message: string}> $pathGaps */ $pathGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */ $evidenceGaps = [];
        $evidenceResult = $this->evidenceKeys($definition->evidenceRecords);
        $evidenceKeys = $evidenceResult['keys'];
        $conflicts = [...$conflicts, ...$evidenceResult['conflicts']];
        $evidenceGaps = [...$evidenceGaps, ...$evidenceResult['gaps']];
        $indexed = [];
        $keys = [];
        $types = ['client_acceptance', 'engagement', 'matter', 'matter_event', 'matter_closure', 'corrective_action'];
        foreach ($definition->records as $record) {
            $key = (string) ($record['key'] ?? '');
            $before = count($conflicts) + count($pathGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_evidence_index_key', 'An Evidence Index record has a missing or duplicate key.');
            }
            $keys[] = $key;
            $type = (string) ($record['artifact_type'] ?? '');
            if (! in_array($type, $types, true) || empty($record['artifact_key'])) {
                $pathGaps[] = $this->issue('evidence_index_artifact_missing', "Evidence Index {$key} lacks a valid artifact type or key.");
            }
            if (in_array($type, ['engagement', 'matter', 'matter_event', 'matter_closure', 'corrective_action'], true) && empty($record['engagement_key'])) {
                $pathGaps[] = $this->issue('evidence_index_engagement_missing', "Evidence Index {$key} lacks its Engagement path.");
            }
            if (in_array($type, ['matter', 'matter_event', 'matter_closure', 'corrective_action'], true) && empty($record['matter_key'])) {
                $pathGaps[] = $this->issue('evidence_index_matter_missing', "Evidence Index {$key} lacks its Matter path.");
            }
            $linkedEvidence = is_array($record['evidence_record_keys'] ?? null) ? array_values($record['evidence_record_keys']) : [];
            if ($linkedEvidence === []) {
                $evidenceGaps[] = $this->issue('evidence_index_links_missing', "Evidence Index {$key} has no linked Evidence Records.");
            }
            foreach ($linkedEvidence as $evidenceKey) {
                if (! in_array($evidenceKey, $evidenceKeys, true)) {
                    $evidenceGaps[] = $this->issue('evidence_index_unknown_evidence', "Evidence Index {$key} references unknown Evidence {$evidenceKey}.");
                }
            }
            $valid = $before === count($conflicts) + count($pathGaps) + count($evidenceGaps);
            $projection = [...$record, 'indexed' => $valid];
            if ($valid) {
                $indexed[] = $projection;
            }
        }

        return new ResolvedEvidenceIndex($definition->schemaVersion, $definition->requirements, $definition->records, $indexed, $definition->evidenceRecords, $conflicts, $pathGaps, $evidenceGaps);
    }

    /** @param list<array<string, mixed>> $records
     * @return array{keys: list<string>, conflicts: list<array{code: string, message: string}>, gaps: list<array{code: string, message: string}>}
     */
    private function evidenceKeys(array $records): array
    {
        $keys = [];
        $conflicts = [];
        $gaps = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_evidence_record_key', 'Evidence Index Evidence has a missing or duplicate key.');
            } elseif (empty($record['record_type']) || empty($record['subject']) || empty($record['actor']) || empty($record['recorded_at']) || empty($record['source']) || empty($record['reason']) || empty($record['state'])) {
                $gaps[] = $this->issue('incomplete_evidence_index_evidence', "Evidence {$key} is incomplete.");
            } else {
                $keys[] = $key;
            }
        }

return compact('keys', 'conflicts', 'gaps');
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
