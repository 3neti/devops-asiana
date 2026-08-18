<?php

namespace App\ControlHistory;

final readonly class InstitutionalControlHistoryDefinition
{
    /** @param list<string> $eventKinds */
    public function __construct(
        public int $schemaVersion,
        public string $historyKey,
        public string $source,
        public bool $includePayloads,
        public array $eventKinds,
        public string $anchorAlgorithm = 'sha256',
        /** @var list<string> */
        public array $ordering = ['occurred_at', 'event_kind', 'event_key'],
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            historyKey: $definition['history_key'],
            source: $definition['source'],
            includePayloads: $definition['include_payloads'],
            eventKinds: array_values($definition['event_kinds']),
            anchorAlgorithm: $definition['anchor_algorithm'] ?? 'sha256',
            ordering: array_values($definition['ordering'] ?? ['occurred_at', 'event_kind', 'event_key']),
        );
    }
}
