<?php

namespace App\ControlHistory;

final readonly class ResolvedInstitutionalControlHistory
{
    /**
     * @param  list<string>  $eventKinds
     * @param  list<array<string, mixed>>  $events
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $historyGaps
     */
    public function __construct(
        public int $schemaVersion,
        public string $historyKey,
        public string $source,
        public bool $payloadsExcluded,
        public array $eventKinds,
        public array $events,
        public array $conflicts,
        public array $historyGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'history_key' => $this->historyKey,
            'source' => $this->source,
            'status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($this->historyGaps !== [] ? 'consistent_with_gaps' : 'consistent'),
            'payloads_excluded' => $this->payloadsExcluded,
            'event_kinds' => $this->eventKinds,
            'events' => $this->events,
            'counts' => [
                'events' => count($this->events),
                'conflicts' => count($this->conflicts),
                'history_gaps' => count($this->historyGaps),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'history_gaps' => $this->historyGaps,
            ],
            'principles' => [
                'History is an append-only projection of source records.',
                'Events retain source kind, reference, actor, and chronology.',
                'History does not create authority, approval, closure, or remediation state.',
                'Payloads and secrets are excluded from the export.',
            ],
        ];
    }
}
