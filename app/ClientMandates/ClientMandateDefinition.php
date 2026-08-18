<?php

namespace App\ClientMandates;

final readonly class ClientMandateDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $actionRequests
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $actionRequests,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self($definition['schema_version'], array_values($definition['requirements']), array_values($definition['action_requests']), array_values($definition['evidence_records']));
    }
}
