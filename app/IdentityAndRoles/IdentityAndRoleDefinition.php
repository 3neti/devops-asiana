<?php

namespace App\IdentityAndRoles;

final readonly class IdentityAndRoleDefinition
{
    /**
     * @param  list<array<string, mixed>>  $identities
     * @param  list<array<string, mixed>>  $roles
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $identities,
        public array $roles,
        public array $assignments,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            identities: array_values($definition['identities']),
            roles: array_values($definition['roles']),
            assignments: array_values($definition['assignments']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
