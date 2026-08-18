<?php

namespace App\ControlExports;

final readonly class ResolvedControlReviewEvidenceExport
{
    /**
     * @param  list<string>  $includedFields
     * @param  list<array<string, mixed>>  $controls
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    public function __construct(
        public int $schemaVersion,
        public string $exportKey,
        public string $source,
        public bool $payloadsExcluded,
        public array $includedFields,
        public string $status,
        public array $controls,
        public array $conflicts,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'export_key' => $this->exportKey,
            'source' => $this->source,
            'status' => $this->status,
            'payloads_excluded' => $this->payloadsExcluded,
            'included_fields' => $this->includedFields,
            'controls' => $this->controls,
            'counts' => [
                'controls' => count($this->controls),
                'attention_required' => count(array_filter(
                    $this->controls,
                    static fn (array $control): bool => $control['status'] === 'attention_required',
                )),
            ],
            'reports' => ['conflicts' => $this->conflicts],
            'principles' => [
                'This export is a stable projection of Institutional Control Review, not a new source of truth.',
                'Gap messages preserve source category and provenance.',
                'Evidence payloads and secrets are excluded from the export.',
                'Exporting a finding never grants authority, accepts risk, creates an exception, or closes remediation.',
            ],
        ];
    }
}
