<?php

namespace App\RetentionFindings;

final readonly class ResolvedRetentionFindingLinks
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $links
     * @param  list<array<string, mixed>>  $resolvedLinks
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $findingGaps
     * @param  list<array{code: string, message: string}>  $actionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $links,
        public array $resolvedLinks,
        public array $conflicts,
        public array $findingGaps,
        public array $actionGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->findingGaps !== [] || $this->actionGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'links' => $this->links,
            'resolved_links' => $this->resolvedLinks,
            'counts' => [
                'links' => count($this->links),
                'resolved_links' => count($this->resolvedLinks),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'finding_gaps' => $this->findingGaps,
                'action_gaps' => $this->actionGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'A retention finding links to an existing Corrective Action; it never creates one.',
                'Linkage does not assign, verify, or close a Corrective Action.',
                'Retention Review and Corrective Action remain separate lifecycles.',
                'Link evidence is explicit and historically attributable.',
            ],
        ];
    }
}
