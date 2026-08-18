<?php

namespace App\ControlHistory;

final readonly class InstitutionalControlHistoryVerificationEvidenceLinkDefinition
{
    /** @param list<array<string, mixed>> $links */
    public function __construct(
        public int $schemaVersion,
        public string $linkRegistryKey,
        public string $source,
        public array $links,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            linkRegistryKey: $definition['link_registry_key'],
            source: $definition['source'],
            links: array_values($definition['links'] ?? []),
        );
    }
}
