<?php

namespace App\MatterClosures;

final readonly class ResolvedMatterClosures
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $closures
     * @param  list<array<string, mixed>>  $projections
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $matterGaps
     * @param  list<array{code: string, message: string}>  $eventGaps
     * @param  list<array{code: string, message: string}>  $actionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(public int $schemaVersion, public array $requirements, public array $closures, public array $projections, public array $evidenceRecords, public array $conflicts, public array $matterGaps, public array $eventGaps, public array $actionGaps, public array $evidenceGaps) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->matterGaps !== [] || $this->eventGaps !== [] || $this->actionGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'closures' => $this->closures,
            'projections' => $this->projections,
            'evidence_records' => $this->evidenceRecords,
            'counts' => ['closures' => count($this->closures), 'projections' => count($this->projections), 'follow_up_complete' => count(array_filter($this->projections, static fn (array $projection): bool => ($projection['follow_up_complete'] ?? false) === true)), 'evidence_records' => count($this->evidenceRecords)],
            'reports' => ['conflicts' => $this->conflicts, 'matter_gaps' => $this->matterGaps, 'event_gaps' => $this->eventGaps, 'action_gaps' => $this->actionGaps, 'evidence_gaps' => $this->evidenceGaps],
            'principles' => [
                'Matter closure and corrective-action closure are separate institutional facts.',
                'A verified Matter closure never erases or closes follow-up obligations.',
                'Outstanding corrective actions remain visible after Matter closure.',
                'Corrective Action completion and verification remain governed by their own compiler.',
                'Closure projections require explicit links and Evidence.',
            ],
        ];
    }
}
