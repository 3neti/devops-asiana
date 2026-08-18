<?php

namespace App\MatterEvents;

final readonly class ResolvedMatterEvents
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $events
     * @param  list<array<string, mixed>>  $admittedEvents
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $matterGaps
     * @param  list<array{code: string, message: string}>  $eventGaps
     * @param  list<array{code: string, message: string}>  $chronologyGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(public int $schemaVersion, public array $requirements, public array $events, public array $admittedEvents, public array $evidenceRecords, public array $conflicts, public array $matterGaps, public array $eventGaps, public array $chronologyGaps, public array $evidenceGaps) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->matterGaps !== [] || $this->eventGaps !== [] || $this->chronologyGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'events' => $this->events,
            'admitted_events' => $this->admittedEvents,
            'evidence_records' => $this->evidenceRecords,
            'counts' => ['events' => count($this->events), 'admitted_events' => count($this->admittedEvents), 'evidence_records' => count($this->evidenceRecords)],
            'reports' => ['conflicts' => $this->conflicts, 'matter_gaps' => $this->matterGaps, 'event_gaps' => $this->eventGaps, 'chronology_gaps' => $this->chronologyGaps, 'evidence_gaps' => $this->evidenceGaps],
            'principles' => [
                'A Matter Event is bounded by one Matter and never creates a Matter or Engagement.',
                'Decision, change, incident, review, and closure remain distinct event types.',
                'Events preserve actor, time, disposition, chronology, and Evidence.',
                'Closure requires independent verification and never erases prior events.',
                'Execution or observation never implies approval, verification, or closure.',
            ],
        ];
    }
}
