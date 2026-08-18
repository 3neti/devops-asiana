<?php

namespace App\Matters;

final readonly class MatterDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $matters
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $matters,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self($definition['schema_version'], array_values($definition['requirements']), array_values($definition['matters']), array_values($definition['evidence_records']));
    }
}
