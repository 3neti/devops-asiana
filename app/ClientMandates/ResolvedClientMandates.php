<?php

namespace App\ClientMandates;

final readonly class ResolvedClientMandates
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $actionRequests
     * @param  list<array<string, mixed>>  $permittedActions
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $mandateGaps
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $approvalGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $actionRequests,
        public array $permittedActions,
        public array $evidenceRecords,
        public array $conflicts,
        public array $mandateGaps,
        public array $authorityGaps,
        public array $approvalGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->mandateGaps !== [] || $this->authorityGaps !== [] || $this->approvalGaps !== [] || $this->evidenceGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'action_requests' => $this->actionRequests,
            'permitted_actions' => $this->permittedActions,
            'evidence_records' => $this->evidenceRecords,
            'counts' => ['action_requests' => count($this->actionRequests), 'permitted_actions' => count($this->permittedActions), 'evidence_records' => count($this->evidenceRecords)],
            'reports' => ['conflicts' => $this->conflicts, 'mandate_gaps' => $this->mandateGaps, 'authority_gaps' => $this->authorityGaps, 'approval_gaps' => $this->approvalGaps, 'evidence_gaps' => $this->evidenceGaps],
            'principles' => [
                'Firm Authority never supplies Client Mandate.',
                'Client Mandate is bounded by Client, Engagement, system, environment, and permitted action.',
                'Specific Approval remains separate from standing mandate and Firm Authority.',
                'Technical access, execution, or an open Engagement never creates permission by itself.',
                'A permitted action is a projection of independently resolved institutional facts.',
            ],
        ];
    }
}
