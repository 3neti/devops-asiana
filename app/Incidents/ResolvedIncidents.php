<?php

namespace App\Incidents;

final readonly class ResolvedIncidents
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, mixed>>  $recordRequirements
     * @param  list<array<string, mixed>>  $incidentRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $readinessGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $incidentRecords,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $conflicts,
        public array $decisionGaps,
        public array $evidenceGaps,
        public array $readinessGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->decisionGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policies' => $this->governingPolicies,
            'record_requirements' => $this->recordRequirements,
            'incident_records' => $this->incidentRecords,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'incident_records' => count($this->incidentRecords),
                'evidence_records' => count($this->evidenceRecords),
                'active_response' => count(array_filter(
                    $this->incidentRecords,
                    static fn (array $incident): bool => ($incident['active_response'] ?? false) === true,
                )),
                'awaiting_closure' => count(array_filter(
                    $this->incidentRecords,
                    static fn (array $incident): bool => ($incident['may_close_incident'] ?? false) === true,
                )),
                'by_lifecycle_status' => $this->lifecycleCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'No Incident Without Disclosure.',
                'An event or alert never silently becomes an Incident.',
                'Incident command and Responsible Partner accountability remain separate.',
                'Service restoration does not imply Incident closure.',
                'Notification decisions are explicit, attributable, and evidenced.',
                'Post-incident review is blameless and produces owned corrective action.',
            ],
        ];
    }
}
