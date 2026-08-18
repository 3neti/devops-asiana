<?php

namespace App\MatterClosures;

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\MatterEvents\ResolvedMatterEvents;

final class ResolveMatterClosures
{
    public function handle(MatterClosureDefinition $definition, ResolvedMatterEvents $events, ResolvedCorrectiveActions $correctiveActions): ResolvedMatterClosures
    {
        /** @var list<array{code: string, message: string}> $conflicts */ $conflicts = [];
        /** @var list<array{code: string, message: string}> $matterGaps */ $matterGaps = [];
        /** @var list<array{code: string, message: string}> $eventGaps */ $eventGaps = [];
        /** @var list<array{code: string, message: string}> $actionGaps */ $actionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */ $evidenceGaps = [];
        $eventIndex = $this->indexByKey($events->admittedEvents);
        $actionIndex = $this->indexByKey($correctiveActions->correctiveActions);
        $evidenceResult = $this->evidenceKeys($definition->evidenceRecords);
        $evidenceKeys = $evidenceResult['keys'];
        $conflicts = [...$conflicts, ...$evidenceResult['conflicts']];
        $evidenceGaps = [...$evidenceGaps, ...$evidenceResult['gaps']];
        $keys = [];
        $closures = [];
        $projections = [];

        foreach ($definition->closures as $closure) {
            $key = (string) ($closure['key'] ?? '');
            $before = count($conflicts) + count($matterGaps) + count($eventGaps) + count($actionGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_matter_closure_key', 'A Matter Closure has a missing or duplicate key.');
            }
            $keys[] = $key;
            $event = $eventIndex[(string) ($closure['closure_event_key'] ?? '')] ?? null;
            $matterKey = (string) ($closure['matter_key'] ?? '');
            if ($event === null || ($event['type'] ?? null) !== 'closure' || ($event['matter_key'] ?? null) !== $matterKey) {
                $eventGaps[] = $this->issue('matter_closure_event_not_admitted', "Matter Closure {$key} lacks an admitted closure Event for the same Matter.");
            }
            if ($matterKey === '') {
                $matterGaps[] = $this->issue('matter_closure_matter_missing', "Matter Closure {$key} has no Matter reference.");
            }
            $actionKeys = is_array($closure['corrective_action_keys'] ?? null) ? array_values($closure['corrective_action_keys']) : [];
            $statuses = [];
            foreach ($actionKeys as $actionKey) {
                $action = $actionIndex[(string) $actionKey] ?? null;
                if ($action === null) {
                    $actionGaps[] = $this->issue('matter_closure_corrective_action_missing', "Matter Closure {$key} references unknown Corrective Action {$actionKey}.");
                } else {
                    $statuses[] = $action['lifecycle_status'] ?? $action['operational_status'] ?? null;
                }
            }
            if (! $this->hasEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('matter_closure_evidence_missing', "Matter Closure {$key} lacks Evidence.");
            }
            $valid = $before === count($conflicts) + count($matterGaps) + count($eventGaps) + count($actionGaps) + count($evidenceGaps);
            $followUpComplete = $actionKeys !== [] && $statuses !== [] && count(array_filter($statuses, static fn (mixed $status): bool => $status === 'closed')) === count($statuses);
            $projection = [...$closure, 'matter_closed' => $valid, 'follow_up_complete' => $followUpComplete, 'outstanding_corrective_action_keys' => array_values(array_filter($actionKeys, static fn (mixed $actionKey): bool => ($actionIndex[(string) $actionKey]['lifecycle_status'] ?? null) !== 'closed'))];
            $closures[] = $projection;
            if ($valid) {
                $projections[] = $projection;
            }
        }

        return new ResolvedMatterClosures($definition->schemaVersion, $definition->requirements, $closures, $projections, $definition->evidenceRecords, $conflicts, $matterGaps, $eventGaps, $actionGaps, $evidenceGaps);
    }

    /** @param list<array<string, mixed>> $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $index = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $index[$record['key']] = $record;
            }
        }

return $index;
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
                $conflicts[] = $this->issue('invalid_matter_closure_evidence_key', 'Matter Closure Evidence has a missing or duplicate key.');
            } elseif (empty($record['record_type']) || empty($record['subject']) || empty($record['actor']) || empty($record['recorded_at']) || empty($record['source']) || empty($record['reason']) || empty($record['state'])) {
                $gaps[] = $this->issue('incomplete_matter_closure_evidence', "Evidence {$key} is incomplete.");
            } else {
                $keys[] = $key;
            }
        }

return compact('keys', 'conflicts', 'gaps');
    }

    /** @param list<string> $evidenceKeys */
    private function hasEvidence(mixed $key, array $evidenceKeys): bool
    {
        return is_string($key) && in_array($key, $evidenceKeys, true);
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
