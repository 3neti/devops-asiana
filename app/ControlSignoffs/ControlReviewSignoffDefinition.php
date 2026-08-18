<?php

namespace App\ControlSignoffs;

final readonly class ControlReviewSignoffDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $signoffs
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $signoffs,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            signoffs: array_values($definition['signoffs']),
        );
    }
}
