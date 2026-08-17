<?php

namespace App\Partnership;

final readonly class PartnershipDefinition
{
    /**
     * @param  array<string, mixed>  $formation
     * @param  array<string, mixed>  $constitution
     * @param  list<InstitutionalDecision>  $decisions
     */
    public function __construct(
        public int $schemaVersion,
        public array $formation,
        public array $constitution,
        public array $decisions,
    ) {}

    /**
     * @param  array{schema_version: int, formation: array<string, mixed>, constitution: array<string, mixed>, decisions: list<array{key: string, label: string, institutional_state: string, legal_state: string, statement: string}>}  $definition
     */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            formation: $definition['formation'],
            constitution: $definition['constitution'],
            decisions: array_map(
                InstitutionalDecision::fromArray(...),
                $definition['decisions'],
            ),
        );
    }
}
