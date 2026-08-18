<?php

namespace App\ControlHistory;

final readonly class ResolvedInstitutionalControlHistoryIntegrity
{
    /**
     * @param  list<string>  $ordering
     * @param  list<array<string, mixed>>  $eventAnchors
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $integrityGaps
     */
    public function __construct(
        public int $schemaVersion,
        public string $integrityKey,
        public string $source,
        public string $algorithm,
        public array $ordering,
        public bool $payloadsExcluded,
        public string $historyAnchor,
        public array $eventAnchors,
        public array $conflicts,
        public array $integrityGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'integrity_key' => $this->integrityKey,
            'source' => $this->source,
            'status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($this->integrityGaps !== [] ? 'consistent_with_gaps' : 'consistent'),
            'algorithm' => $this->algorithm,
            'ordering' => $this->ordering,
            'payloads_excluded' => $this->payloadsExcluded,
            'history_anchor' => $this->historyAnchor,
            'event_anchors' => $this->eventAnchors,
            'counts' => [
                'event_anchors' => count($this->eventAnchors),
                'conflicts' => count($this->conflicts),
                'integrity_gaps' => count($this->integrityGaps),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'integrity_gaps' => $this->integrityGaps,
            ],
            'principles' => [
                'The anchor is a deterministic integrity projection, not a source of authority or approval.',
                'Only payload-free event identity fields participate in hashing.',
                'Ordering anomalies and unsupported integrity inputs remain visible as gaps.',
                'Anchoring never mutates or closes source history.',
            ],
        ];
    }
}
