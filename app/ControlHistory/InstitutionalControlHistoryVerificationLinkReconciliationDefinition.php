<?php

namespace App\ControlHistory;

final readonly class InstitutionalControlHistoryVerificationLinkReconciliationDefinition
{
    /** @param list<array<string, mixed>> $reconciliations */
    public function __construct(
        public int $schemaVersion,
        public string $reconciliationKey,
        public string $source,
        public array $reconciliations,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            reconciliationKey: $definition['reconciliation_key'],
            source: $definition['source'],
            reconciliations: array_values($definition['reconciliations'] ?? []),
        );
    }
}
