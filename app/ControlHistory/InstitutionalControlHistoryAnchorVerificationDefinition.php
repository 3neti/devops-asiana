<?php

namespace App\ControlHistory;

final readonly class InstitutionalControlHistoryAnchorVerificationDefinition
{
    /** @param list<array<string, mixed>> $suppliedEventAnchors */
    public function __construct(
        public int $schemaVersion,
        public string $verificationKey,
        public string $source,
        public string $algorithm,
        public ?string $suppliedHistoryAnchor,
        public array $suppliedEventAnchors,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            verificationKey: $definition['verification_key'],
            source: $definition['source'],
            algorithm: $definition['algorithm'],
            suppliedHistoryAnchor: $definition['supplied_history_anchor'] ?? null,
            suppliedEventAnchors: array_values($definition['supplied_event_anchors'] ?? []),
        );
    }
}
