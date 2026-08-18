<?php

namespace App\ControlHistory;

use JsonException;

final class ResolveInstitutionalControlHistoryIntegrity
{
    /**
     * @param  list<string>  $identityFields
     */
    private const IDENTITY_FIELDS = [
        'event_key',
        'event_kind',
        'source_reference',
        'occurred_at',
        'actor',
        'state',
    ];

    public function handle(
        InstitutionalControlHistoryIntegrityDefinition $definition,
        ResolvedInstitutionalControlHistory $history,
    ): ResolvedInstitutionalControlHistoryIntegrity {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $integrityGaps */
        $integrityGaps = [];

        if ($definition->algorithm !== 'sha256') {
            $conflicts[] = $this->issue('unsupported_anchor_algorithm', "Anchor algorithm {$definition->algorithm} is not supported.");
        }
        if ($definition->includePayloads) {
            $conflicts[] = $this->issue('payload_anchor_forbidden', 'History integrity anchors cannot include payloads or secrets.');
        }
        if ($definition->source !== $history->source) {
            $conflicts[] = $this->issue('history_source_mismatch', 'Integrity definition source does not match the resolved history source.');
        }
        if ($definition->ordering !== ['occurred_at', 'event_kind', 'event_key']) {
            $conflicts[] = $this->issue('unsupported_history_ordering', 'Integrity anchoring requires occurred_at, event_kind, event_key ordering.');
        }

        foreach ($history->historyGaps as $gap) {
            $integrityGaps[] = $this->issue('source_history_gap', $gap['code'].': '.$gap['message']);
        }

        $events = $history->events;
        $canonicalEvents = $events;
        usort($canonicalEvents, fn (array $left, array $right): int => $this->compareEvents($left, $right));

        if ($this->eventKeys($events) !== $this->eventKeys($canonicalEvents)) {
            $integrityGaps[] = $this->issue('history_ordering_mismatch', 'Source history is not in the configured stable order.');
        }

        $seenKeys = [];
        $eventAnchors = [];
        foreach ($canonicalEvents as $event) {
            $identity = $this->identity($event);
            $eventKey = (string) ($identity['event_key'] ?? '');
            if ($eventKey === '') {
                $integrityGaps[] = $this->issue('history_event_key_missing', 'A history event lacks an event key.');
            } elseif (isset($seenKeys[$eventKey])) {
                $integrityGaps[] = $this->issue('duplicate_history_event_key', "History event key {$eventKey} occurs more than once.");
            }
            $seenKeys[$eventKey] = true;
            $eventAnchors[] = [
                ...$identity,
                'anchor' => $this->hash($identity),
            ];
        }

        $envelope = [
            'schema_version' => $definition->schemaVersion,
            'integrity_key' => $definition->integrityKey,
            'source' => $definition->source,
            'algorithm' => $definition->algorithm,
            'ordering' => $definition->ordering,
            'events' => array_map(
                static fn (array $event): array => $event,
                $eventAnchors,
            ),
        ];

        return new ResolvedInstitutionalControlHistoryIntegrity(
            schemaVersion: $definition->schemaVersion,
            integrityKey: $definition->integrityKey,
            source: $definition->source,
            algorithm: $definition->algorithm,
            ordering: $definition->ordering,
            payloadsExcluded: ! $definition->includePayloads,
            historyAnchor: $this->hash($envelope),
            eventAnchors: $eventAnchors,
            conflicts: $conflicts,
            integrityGaps: $integrityGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function identity(array $event): array
    {
        $identity = [];
        foreach (self::IDENTITY_FIELDS as $field) {
            $identity[$field] = $event[$field] ?? null;
        }

        return $identity;
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    private function compareEvents(array $left, array $right): int
    {
        foreach (['occurred_at', 'event_kind', 'event_key'] as $field) {
            $comparison = strcmp((string) ($left[$field] ?? ''), (string) ($right[$field] ?? ''));
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return list<string>
     */
    private function eventKeys(array $events): array
    {
        return array_map(function (array $event): string {
            return $this->hash($this->identity($event));
        }, $events);
    }

    /** @param array<string, mixed> $value */
    private function hash(array $value): string
    {
        try {
            return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (JsonException $exception) {
            throw new \RuntimeException('Unable to serialize Institutional Control History integrity input.', previous: $exception);
        }
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
