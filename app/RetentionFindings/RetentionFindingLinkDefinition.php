<?php

namespace App\RetentionFindings;

final readonly class RetentionFindingLinkDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $links
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $links,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            links: array_values($definition['links']),
        );
    }
}
