<?php

namespace App\FormationBootstrap;

final readonly class ResolvedFormationBootstrap
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, string>>  $eligiblePolicyVersions
     * @param  array<string, mixed>  $consentRule
     * @param  list<array<string, mixed>>  $ratificationRecords
     * @param  list<array<string, mixed>>  $policyApprovals
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $formationGaps
     * @param  list<array{code: string, message: string}>  $consentGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $counselReview
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $eligiblePolicyVersions,
        public array $consentRule,
        public array $ratificationRecords,
        public array $policyApprovals,
        public array $evidenceRecords,
        public array $conflicts,
        public array $formationGaps,
        public array $consentGaps,
        public array $evidenceGaps,
        public array $counselReview,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->formationGaps !== [], $this->consentGaps !== [], $this->evidenceGaps !== [], $this->counselReview !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'requirements' => $this->requirements,
            'eligible_policy_versions' => $this->eligiblePolicyVersions,
            'consent_rule' => $this->consentRule,
            'ratification_records' => $this->ratificationRecords,
            'policy_approval_bases' => $this->policyApprovals,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'ratifications' => count(array_filter($this->ratificationRecords, static fn (array $record): bool => $record['ratification_verified'] === true)),
                'policy_approval_bases' => count($this->policyApprovals),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'formation_gaps' => $this->formationGaps,
                'consent_gaps' => $this->consentGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'counsel_review' => $this->counselReview,
            ],
            'principles' => [
                'Formation ratification is a constitutional bootstrap source, not an ordinary post-formation governance meeting.',
                'Every Founding Partner consent is explicit, attributable, and evidenced; silence is not consent.',
                'Ratification supplies approval basis only for the exact allowlisted initial Policy Versions.',
                'Publication and activation remain separate Policy Registry records.',
                'No Partnership Agreement, effective date, consent rule, or legal sufficiency is inferred.',
            ],
        ];
    }
}
