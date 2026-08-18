<?php

namespace App\ControlHistory;

final readonly class ResolvedInstitutionalControlHistoryVerificationEvidenceLinks
{
    /**
     * @param  list<array<string, mixed>>  $links
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $linkGaps
     */
    public function __construct(
        public int $schemaVersion,
        public string $linkRegistryKey,
        public string $source,
        public array $links,
        public array $conflicts,
        public array $linkGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $status = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->links === [] => 'not_supplied',
            $this->linkGaps !== [] => 'consistent_with_gaps',
            default => 'consistent',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'link_registry_key' => $this->linkRegistryKey,
            'source' => $this->source,
            'status' => $status,
            'links' => $this->links,
            'counts' => [
                'links' => count($this->links),
                'conflicts' => count($this->conflicts),
                'link_gaps' => count($this->linkGaps),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'link_gaps' => $this->linkGaps,
            ],
            'principles' => [
                'A link associates an external artifact reference with an exact verification snapshot.',
                'The link does not admit Evidence, store payloads, or certify the underlying artifact.',
                'Verification, artifact custody, authority, and workflow remain separate facts.',
                'Missing or inconsistent linkage remains visible and never mutates history.',
            ],
        ];
    }
}
