<?php

namespace App\MatterEvents;

final readonly class MatterEventDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $events
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(public int $schemaVersion, public array $requirements, public array $events, public array $evidenceRecords) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self($definition['schema_version'], array_values($definition['requirements']), array_values($definition['events']), array_values($definition['evidence_records']));
    }
}
