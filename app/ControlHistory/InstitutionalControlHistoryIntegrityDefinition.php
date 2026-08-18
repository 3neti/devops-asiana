<?php

namespace App\ControlHistory;

final readonly class InstitutionalControlHistoryIntegrityDefinition
{
    /** @param list<string> $ordering */
    public function __construct(
        public int $schemaVersion,
        public string $integrityKey,
        public string $source,
        public string $algorithm,
        public array $ordering,
        public bool $includePayloads,
    ) {}

    public static function fromHistoryDefinition(InstitutionalControlHistoryDefinition $definition): self
    {
        return new self(
            schemaVersion: $definition->schemaVersion,
            integrityKey: $definition->historyKey.'-integrity',
            source: $definition->source,
            algorithm: $definition->anchorAlgorithm,
            ordering: $definition->ordering,
            includePayloads: $definition->includePayloads,
        );
    }
}
