<?php

namespace App\DecisionRecords;

final readonly class ResolvedDecisionRecords
{
    /**
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  list<array<string, string>>  $recordRequirements
     * @param  list<array<string, mixed>>  $decisions
     * @param  list<array<string, mixed>>  $executions
     * @param  list<array<string, mixed>>  $verifications
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $readinessGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $governingPolicies,
        public array $recordRequirements,
        public array $decisions,
        public array $executions,
        public array $verifications,
        public array $evidenceRecords,
        public array $lifecycleCounts,
        public array $conflicts,
        public array $authorityGaps,
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
                $this->authorityGaps !== [], $this->decisionGaps !== [], $this->evidenceGaps !== [], $this->readinessGaps !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'governing_policies' => $this->governingPolicies,
            'record_requirements' => $this->recordRequirements,
            'decision_records' => $this->decisions,
            'execution_records' => $this->executions,
            'verification_records' => $this->verifications,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'decisions' => count($this->decisions),
                'executable_decisions' => count(array_filter($this->decisions, static fn (array $decision): bool => $decision['may_execute'] === true)),
                'executions' => count($this->executions),
                'verifications' => count($this->verifications),
                'evidence_records' => count($this->evidenceRecords),
                'by_lifecycle' => $this->lifecycleCounts,
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'authority_gaps' => $this->authorityGaps,
                'decision_gaps' => $this->decisionGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'readiness_gaps' => $this->readinessGaps,
            ],
            'principles' => [
                'A Decision Record cites one exact effective Authority Matrix entry and one effective holder.',
                'Proposal, review, risk acceptance, decision, execution, verification, and Evidence are separate facts.',
                'Execution never supplies missing approval, and verification never rewrites the decision.',
                'This first slice records Firm governance and management decisions only; it does not supply Client Mandate.',
                'Institutional history is append-only in intent: supersession and withdrawal preserve prior records.',
            ],
        ];
    }
}
