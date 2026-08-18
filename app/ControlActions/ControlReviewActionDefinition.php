<?php

namespace App\ControlActions;

final readonly class ControlReviewActionDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $actions
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $actions,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            actions: array_values($definition['actions']),
        );
    }
}
