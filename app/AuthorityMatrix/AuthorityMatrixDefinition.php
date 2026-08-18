<?php

namespace App\AuthorityMatrix;

final readonly class AuthorityMatrixDefinition
{
    /**
     * @param  array<string, string>  $governingPolicy
     * @param  list<array<string, mixed>>  $domains
     * @param  list<array<string, mixed>>  $entries
     * @param  list<array<string, string>>  $deferredDecisions
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicy,
        public array $domains,
        public array $entries,
        public array $deferredDecisions,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            governingPolicy: $definition['governing_policy'],
            domains: array_values($definition['domains']),
            entries: array_values($definition['entries']),
            deferredDecisions: array_values($definition['deferred_decisions']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
