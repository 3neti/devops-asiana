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
        );
    }
}
