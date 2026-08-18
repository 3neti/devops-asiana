<?php

namespace App\MatterEvents;

use App\Matters\ResolvedMatters;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveMatterEvents
{
    public function handle(MatterEventDefinition $definition, ResolvedMatters $matters, ?DateTimeImmutable $asOf = null): ResolvedMatterEvents
    {
        /** @var list<array{code: string, message: string}> $conflicts */ $conflicts = [];
        /** @var list<array{code: string, message: string}> $matterGaps */ $matterGaps = [];
        /** @var list<array{code: string, message: string}> $eventGaps */ $eventGaps = [];
        /** @var list<array{code: string, message: string}> $chronologyGaps */ $chronologyGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */ $evidenceGaps = [];
        $asOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $matterIndex = $this->indexByKey($matters->accountabilityProjections);
        $evidenceResult = $this->evidenceKeys($definition->evidenceRecords);
        $evidenceKeys = $evidenceResult['keys'];
        $conflicts = [...$conflicts, ...$evidenceResult['conflicts']];
        $evidenceGaps = [...$evidenceGaps, ...$evidenceResult['gaps']];
        $keys = [];
        $lastByMatter = [];
        $resolved = [];
        $admitted = [];
        $types = ['decision', 'change', 'incident', 'review', 'closure'];

        foreach ($definition->events as $event) {
            $key = (string) ($event['key'] ?? '');
            $before = count($conflicts) + count($matterGaps) + count($eventGaps) + count($chronologyGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_matter_event_key', 'A Matter Event has a missing or duplicate key.');
            }
            $keys[] = $key;
            $matterKey = (string) ($event['matter_key'] ?? '');
            $matter = $matterIndex[$matterKey] ?? null;
            if ($matter === null) {
                $matterGaps[] = $this->issue('matter_event_parent_missing', "Matter Event {$key} does not reference a known Matter.");
            }
            $type = (string) ($event['type'] ?? '');
            if (! in_array($type, $types, true) || ($event['status'] ?? null) !== 'recorded' || empty($event['actor_key']) || empty($event['summary'])) {
                $eventGaps[] = $this->issue('matter_event_incomplete', "Matter Event {$key} lacks a valid type, recorded status, actor, or summary.");
            }
            $occurredAt = $this->date($event['occurred_at'] ?? null);
            if ($occurredAt === null || $occurredAt->isAfter($asOf)) {
                $chronologyGaps[] = $this->issue('matter_event_time_invalid', "Matter Event {$key} is undated or future-dated.");
            } elseif (isset($lastByMatter[$matterKey]) && $occurredAt->isBefore($lastByMatter[$matterKey])) {
                $chronologyGaps[] = $this->issue('matter_event_out_of_order', "Matter Event {$key} precedes an earlier recorded event for the Matter.");
            } else {
                $lastByMatter[$matterKey] = $occurredAt;
            }
            if ($type === 'closure') {
                $verifiedBy = $event['verified_by_key'];
                if (empty($event['disposition']) || empty($verifiedBy) || $verifiedBy === ($event['actor_key'] ?? null)) {
                    $eventGaps[] = $this->issue('matter_closure_not_independently_verified', "Matter closure {$key} lacks an independent verifier and disposition.");
                }
            }
            if (! $this->hasEvidence($event['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('matter_event_evidence_missing', "Matter Event {$key} lacks Evidence.");
            }
            $valid = $before === count($conflicts) + count($matterGaps) + count($eventGaps) + count($chronologyGaps) + count($evidenceGaps);
            $projection = [...$event, 'matter_title' => $matter['title'] ?? null, 'admitted' => $valid];
            $resolved[] = $projection;
            if ($valid) {
                $admitted[] = $projection;
            }
        }

        return new ResolvedMatterEvents($definition->schemaVersion, $definition->requirements, $resolved, $admitted, $definition->evidenceRecords, $conflicts, $matterGaps, $eventGaps, $chronologyGaps, $evidenceGaps);
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
                $conflicts[] = $this->issue('invalid_matter_event_evidence_key', 'Matter Event Evidence has a missing or duplicate key.');
            } elseif (empty($record['record_type']) || empty($record['subject']) || empty($record['actor']) || empty($record['recorded_at']) || empty($record['source']) || empty($record['reason']) || empty($record['state'])) {
                $gaps[] = $this->issue('incomplete_matter_event_evidence', "Evidence {$key} is incomplete.");
            } else {
                $keys[] = $key;
            }
        }

        return compact('keys', 'conflicts', 'gaps');
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
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
