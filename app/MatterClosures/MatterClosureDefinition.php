<?php

namespace App\MatterClosures;

final readonly class MatterClosureDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $closures
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(public int $schemaVersion, public array $requirements, public array $closures, public array $evidenceRecords) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self($definition['schema_version'], array_values($definition['requirements']), array_values($definition['closures']), array_values($definition['evidence_records']));
    }
}
