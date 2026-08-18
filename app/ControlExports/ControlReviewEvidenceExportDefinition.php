<?php

namespace App\ControlExports;

final readonly class ControlReviewEvidenceExportDefinition
{
    /** @param list<string> $includedFields */
    public function __construct(
        public int $schemaVersion,
        public string $exportKey,
        public string $source,
        public bool $includeGapMessages,
        public bool $includePayloads,
        public array $includedFields,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            exportKey: $definition['export_key'],
            source: $definition['source'],
            includeGapMessages: $definition['include_gap_messages'],
            includePayloads: $definition['include_payloads'],
            includedFields: array_values($definition['included_fields']),
        );
    }
}
