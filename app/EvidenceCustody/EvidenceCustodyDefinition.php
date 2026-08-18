<?php

namespace App\EvidenceCustody;

final readonly class EvidenceCustodyDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $records
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $records,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            records: array_values($definition['records']),
        );
    }
}
