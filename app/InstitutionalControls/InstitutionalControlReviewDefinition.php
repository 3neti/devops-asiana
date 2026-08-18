<?php

namespace App\InstitutionalControls;

final readonly class InstitutionalControlReviewDefinition
{
    /** @param list<array<string, string>> $controls */
    public function __construct(
        public int $schemaVersion,
        public array $controls,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            controls: array_values($definition['controls']),
        );
    }
}
