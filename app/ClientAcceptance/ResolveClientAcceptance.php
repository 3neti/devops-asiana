<?php

namespace App\ClientAcceptance;

use App\Policies\PolicyLifecycleStatus;
use App\Policies\PolicyRegistryDefinition;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveClientAcceptance
{
    public function handle(
        ClientAcceptanceDefinition $definition,
        PolicyRegistryDefinition $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedClientAcceptance {
        $conflicts = [];
        $decisionGaps = [];
        $evidenceGaps = [];
        $readinessGaps = [];
        $reviewCounts = array_fill_keys(array_map(
            static fn (AcceptanceReviewStatus $status): string => $status->value,
            AcceptanceReviewStatus::cases(),
        ), 0);
        $outcomeCounts = array_fill_keys(array_map(
            static fn (AcceptanceOutcome $outcome): string => $outcome->value,
            AcceptanceOutcome::cases(),
        ), 0);

        $assessmentKeys = $this->validateAssessmentStandard(
            $definition->requiredAssessments,
            $conflicts,
        );
        $evidenceKeys = $this->validateEvidenceRecords(
            $definition->evidenceRecords,
            $conflicts,
            $evidenceGaps,
        );
        $governingPolicy = $this->resolveGoverningPolicy(
            $definition,
            $policyRegistry,
            $conflicts,
            $readinessGaps,
        );

        $resolvedClients = [];
        $clientKeys = [];

        foreach ($definition->prospectiveClients as $prospectiveClient) {
            $resolvedClients[] = $this->resolveProspectiveClient(
                prospectiveClient: $prospectiveClient,
                assessmentKeys: $assessmentKeys,
                evidenceKeys: $evidenceKeys,
                governingPolicyOperative: $governingPolicy['operative'],
                asOf: Carbon::instance($asOf ?? new DateTimeImmutable),
                clientKeys: $clientKeys,
                reviewCounts: $reviewCounts,
                outcomeCounts: $outcomeCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedClientAcceptance(
            schemaVersion: $definition->schemaVersion,
            governingPolicy: $governingPolicy,
            requiredAssessments: $definition->requiredAssessments,
            prospectiveClients: $resolvedClients,
            evidenceRecords: $definition->evidenceRecords,
            reviewCounts: $reviewCounts,
            outcomeCounts: $outcomeCounts,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
            readinessGaps: $readinessGaps,
        );
    }

    /**
     * @param  list<array{key: string, label: string, question: string}>  $assessments
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return list<string>
     */
    private function validateAssessmentStandard(array $assessments, array &$conflicts): array
    {
        $keys = [];

        foreach ($assessments as $assessment) {
            $key = $assessment['key'];

            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue(
                    'invalid_assessment_standard',
                    'A required Client Acceptance assessment has a missing or duplicate key.',
                );
            }

            if ($assessment['label'] === '' || $assessment['question'] === '') {
                $conflicts[] = $this->issue(
                    'incomplete_assessment_standard',
                    "Required assessment {$key} is incomplete.",
                );
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<string>
     */
    private function validateEvidenceRecords(
        array $records,
        array &$conflicts,
        array &$evidenceGaps,
    ): array {
        $keys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');

            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue(
                    'invalid_evidence_key',
                    'A Client Acceptance Evidence Record has a missing or duplicate key.',
                );
            }

            if (
                empty($record['record_type'])
                || empty($record['subject'])
                || empty($record['actor'])
                || empty($record['recorded_at'])
                || empty($record['source'])
                || empty($record['reason'])
                || empty($record['state'])
            ) {
                $evidenceGaps[] = $this->issue(
                    'incomplete_evidence_record',
                    "Evidence Record {$key} is incomplete.",
                );
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $readinessGaps
     * @return array<string, mixed>
     */
    private function resolveGoverningPolicy(
        ClientAcceptanceDefinition $definition,
        PolicyRegistryDefinition $policyRegistry,
        array &$conflicts,
        array &$readinessGaps,
    ): array {
        $policyKey = $definition->governingPolicy['key'];
        $versionNumber = $definition->governingPolicy['version'];
        $policy = collect($policyRegistry->policies)->firstWhere('key', $policyKey);

        if ($policy === null) {
            $conflicts[] = $this->issue('missing_governing_policy', 'Client Acceptance references an unknown governing policy.');

            return [
                ...$definition->governingPolicy,
                'title' => 'Unknown Policy',
                'status' => 'missing',
                'status_label' => 'Missing',
                'operative' => false,
            ];
        }

        $version = collect($policy->versions)->firstWhere('version', $versionNumber);

        if (! is_array($version)) {
            $conflicts[] = $this->issue('missing_governing_policy_version', 'Client Acceptance references an unknown policy version.');

            return [
                ...$definition->governingPolicy,
                'title' => $policy->title,
                'status' => 'missing',
                'status_label' => 'Missing',
                'operative' => false,
            ];
        }

        $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
        $operative = $status === PolicyLifecycleStatus::Effective;

        if (! $operative) {
            $readinessGaps[] = $this->issue(
                'governing_policy_not_effective',
                "{$policy->title} version {$versionNumber} is not Effective.",
            );
        }

        return [
            ...$definition->governingPolicy,
            'title' => $policy->title,
            'status' => $status === null ? 'invalid' : $status->value,
            'status_label' => $status === null ? 'Invalid' : $status->label(),
            'operative' => $operative,
        ];
    }

    /**
     * @param  array<string, mixed>  $prospectiveClient
     * @param  list<string>  $assessmentKeys
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $clientKeys
     * @param  array<string, int>  $reviewCounts
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveProspectiveClient(
        array $prospectiveClient,
        array $assessmentKeys,
        array $evidenceKeys,
        bool $governingPolicyOperative,
        Carbon $asOf,
        array &$clientKeys,
        array &$reviewCounts,
        array &$outcomeCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $key = (string) ($prospectiveClient['key'] ?? '');
        $name = (string) ($prospectiveClient['legal_name'] ?? $key);
        $status = AcceptanceReviewStatus::tryFrom((string) ($prospectiveClient['review_status'] ?? ''));

        if ($key === '' || in_array($key, $clientKeys, true)) {
            $conflicts[] = $this->issue('invalid_prospective_client_key', 'A Prospective Client has a missing or duplicate key.');
        }

        $clientKeys[] = $key;

        if ($status === null) {
            $conflicts[] = $this->issue('invalid_acceptance_review_status', "{$name} has an invalid acceptance review status.");
        } else {
            $reviewCounts[$status->value]++;
        }

        if (
            $name === ''
            || empty($prospectiveClient['jurisdiction'])
            || empty($prospectiveClient['entity_type'])
            || empty($prospectiveClient['proposed_scope'])
        ) {
            $decisionGaps[] = $this->issue('incomplete_prospective_client_identity', "{$name} has incomplete identity or proposed-scope information.");
        }

        if (in_array($status, [AcceptanceReviewStatus::UnderReview, AcceptanceReviewStatus::DecisionRecorded, AcceptanceReviewStatus::Expired], true) && empty($prospectiveClient['reviewers'])) {
            $decisionGaps[] = $this->issue('missing_acceptance_reviewers', "{$name} has no recorded acceptance reviewers.");
        }

        $assessments = [];

        if (is_array($prospectiveClient['assessments'] ?? null)) {
            foreach ($prospectiveClient['assessments'] as $assessment) {
                if (is_array($assessment)) {
                    $assessments[] = $assessment;
                } else {
                    $conflicts[] = $this->issue('invalid_acceptance_assessment', "{$name} contains a malformed assessment.");
                }
            }
        }

        $hasUnresolvedAssessment = $this->validateAssessments(
            name: $name,
            assessments: $assessments,
            requiredKeys: $assessmentKeys,
            evidenceKeys: $evidenceKeys,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
        );
        $hasUnresolvedRelatedParty = $this->validateRelatedParties(
            $name,
            $prospectiveClient['related_parties'] ?? [],
            $conflicts,
            $decisionGaps,
        );

        $decision = $prospectiveClient['decision'] ?? null;
        $resolvedDecision = null;

        if (in_array($status, [AcceptanceReviewStatus::DecisionRecorded, AcceptanceReviewStatus::Expired], true) && ! is_array($decision)) {
            $decisionGaps[] = $this->issue('missing_acceptance_decision', "{$name} has no explicit Client Acceptance decision.");
        }

        if (in_array($status, [AcceptanceReviewStatus::Identified, AcceptanceReviewStatus::UnderReview], true) && is_array($decision)) {
            $conflicts[] = $this->issue('premature_acceptance_decision', "{$name} has a decision before its review status records one.");
        }

        if (is_array($decision)) {
            $resolvedDecision = $this->resolveDecision(
                name: $name,
                decision: $decision,
                reviewStatus: $status,
                hasUnresolvedAssessment: $hasUnresolvedAssessment || $hasUnresolvedRelatedParty,
                governingPolicyOperative: $governingPolicyOperative,
                evidenceKeys: $evidenceKeys,
                asOf: $asOf,
                outcomeCounts: $outcomeCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        $institutionalStatus = match (true) {
            $status === AcceptanceReviewStatus::Expired,
            ($resolvedDecision['temporal_state'] ?? null) === 'past_validity' => 'acceptance_expired',
            ($resolvedDecision['permits_engagement_consideration'] ?? false) === true => 'accepted_client',
            default => 'prospective_client',
        };

        return [
            ...$prospectiveClient,
            'review_status_label' => $status?->label() ?? 'Invalid',
            'decision' => $resolvedDecision,
            'institutional_status' => $institutionalStatus,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $assessments
     * @param  list<string>  $requiredKeys
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateAssessments(
        string $name,
        array $assessments,
        array $requiredKeys,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $recordedKeys = [];
        $hasUnresolved = false;

        foreach ($assessments as $assessment) {
            $key = (string) ($assessment['key'] ?? '');
            $status = AssessmentStatus::tryFrom((string) ($assessment['status'] ?? ''));

            if (! in_array($key, $requiredKeys, true)) {
                $conflicts[] = $this->issue('unknown_acceptance_assessment', "{$name} contains unknown assessment {$key}.");
                $hasUnresolved = true;
            }

            if (in_array($key, $recordedKeys, true)) {
                $conflicts[] = $this->issue('duplicate_acceptance_assessment', "{$name} contains duplicate assessment {$key}.");
                $hasUnresolved = true;
            }

            $recordedKeys[] = $key;

            if ($status === null) {
                $conflicts[] = $this->issue('invalid_assessment_status', "{$name} assessment {$key} has an invalid status.");
                $hasUnresolved = true;
            } elseif ($status === AssessmentStatus::Unresolved) {
                $hasUnresolved = true;
            }

            if (empty($assessment['summary'])) {
                $decisionGaps[] = $this->issue('missing_assessment_summary', "{$name} assessment {$key} has no summary.");
                $hasUnresolved = true;
            }

            if ($status === AssessmentStatus::ConcernIdentified && (empty($assessment['disposition']) || empty($assessment['risk_owner']))) {
                $decisionGaps[] = $this->issue('undisposed_acceptance_concern', "{$name} assessment {$key} identifies a concern without disposition and risk owner.");
                $hasUnresolved = true;
            }

            if ($status !== AssessmentStatus::NotApplicable) {
                $references = is_array($assessment['evidence_record_keys'] ?? null) ? $assessment['evidence_record_keys'] : [];

                if ($references === []) {
                    $evidenceGaps[] = $this->issue('missing_assessment_evidence', "{$name} assessment {$key} has no supporting Evidence Record.");
                    $hasUnresolved = true;
                }

                foreach ($references as $reference) {
                    if (! in_array($reference, $evidenceKeys, true)) {
                        $evidenceGaps[] = $this->issue('unknown_assessment_evidence', "{$name} assessment {$key} references unknown evidence {$reference}.");
                        $hasUnresolved = true;
                    }
                }
            }
        }

        foreach (array_diff($requiredKeys, $recordedKeys) as $missingKey) {
            $decisionGaps[] = $this->issue('missing_required_assessment', "{$name} is missing required assessment {$missingKey}.");
            $hasUnresolved = true;
        }

        return $hasUnresolved;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateRelatedParties(
        string $name,
        mixed $relatedParties,
        array &$conflicts,
        array &$decisionGaps,
    ): bool {
        if (! is_array($relatedParties)) {
            $conflicts[] = $this->issue('invalid_related_party_register', "{$name} has an invalid related-party register.");

            return true;
        }

        $hasUnresolved = false;

        foreach ($relatedParties as $relatedParty) {
            if (! is_array($relatedParty) || empty($relatedParty['party']) || empty($relatedParty['relationship'])) {
                $decisionGaps[] = $this->issue('incomplete_related_party', "{$name} has an incomplete related-party record.");
                $hasUnresolved = true;

                continue;
            }

            if (($relatedParty['disclosed'] ?? false) !== true || empty($relatedParty['disposition'])) {
                $decisionGaps[] = $this->issue('undisposed_related_party', "{$name} has a related-party relationship without disclosure and disposition.");
                $hasUnresolved = true;
            }
        }

        return $hasUnresolved;
    }

    /**
     * @param  array<string, mixed>  $decision
     * @param  list<string>  $evidenceKeys
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveDecision(
        string $name,
        array $decision,
        ?AcceptanceReviewStatus $reviewStatus,
        bool $hasUnresolvedAssessment,
        bool $governingPolicyOperative,
        array $evidenceKeys,
        Carbon $asOf,
        array &$outcomeCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $outcome = AcceptanceOutcome::tryFrom((string) ($decision['outcome'] ?? ''));

        if ($outcome === null) {
            $conflicts[] = $this->issue('invalid_acceptance_outcome', "{$name} has an invalid Client Acceptance outcome.");
        } else {
            $outcomeCounts[$outcome->value]++;
        }

        if (
            empty($decision['decision_maker'])
            || empty($decision['authority_basis'])
            || empty($decision['decided_at'])
        ) {
            $decisionGaps[] = $this->issue('incomplete_acceptance_authority', "{$name} has an incomplete decision-maker or authority record.");
        }

        $evidenceKey = (string) ($decision['evidence_record_key'] ?? '');

        if ($evidenceKey === '' || ! in_array($evidenceKey, $evidenceKeys, true)) {
            $evidenceGaps[] = $this->issue('missing_acceptance_decision_evidence', "{$name} decision is not linked to a known Evidence Record.");
        }

        if (! $governingPolicyOperative) {
            $conflicts[] = $this->issue('decision_under_inoperative_policy', "{$name} has a Client Acceptance decision under a policy that is not Effective.");
        }

        if ($outcome?->permitsEngagementConsideration() === true) {
            if ($hasUnresolvedAssessment) {
                $conflicts[] = $this->issue('acceptance_with_unresolved_assessment', "{$name} is accepted while a required assessment remains unresolved.");
            }

            if (empty($decision['risk_classification'])) {
                $decisionGaps[] = $this->issue('missing_acceptance_risk_classification', "{$name} acceptance has no risk classification.");
            }
        }

        $conditions = is_array($decision['conditions'] ?? null) ? $decision['conditions'] : [];

        if ($outcome === AcceptanceOutcome::AcceptedWithConditions && $conditions === []) {
            $decisionGaps[] = $this->issue('missing_acceptance_conditions', "{$name} is conditionally accepted without recorded conditions.");
        }

        if ($outcome === AcceptanceOutcome::Accepted && $conditions !== []) {
            $conflicts[] = $this->issue('unclassified_acceptance_conditions', "{$name} is marked Accepted while carrying conditions.");
        }

        if ($outcome === AcceptanceOutcome::Rejected && empty($decision['reason'])) {
            $decisionGaps[] = $this->issue('missing_rejection_reason', "{$name} is rejected without a recorded reason.");
        }

        $decidedAt = $this->date($decision['decided_at'] ?? null);
        $validUntil = $this->date($decision['valid_until'] ?? null);

        if ($outcome?->permitsEngagementConsideration() === true && $validUntil === null) {
            $decisionGaps[] = $this->issue('missing_acceptance_validity', "{$name} acceptance has no validity or re-review date.");
        }

        if ($decidedAt !== null && $validUntil !== null && $validUntil->lessThanOrEqualTo($decidedAt)) {
            $conflicts[] = $this->issue('invalid_acceptance_validity', "{$name} acceptance does not remain valid after its decision date.");
        }

        $isPastValidity = $validUntil?->lessThanOrEqualTo($asOf) ?? false;

        if (
            $outcome?->permitsEngagementConsideration() === true
            && $isPastValidity
            && $reviewStatus !== AcceptanceReviewStatus::Expired
        ) {
            $conflicts[] = $this->issue('accepted_client_past_validity', "{$name} remains accepted after its validity expired.");
        }

        $hasCompleteAuthority = ! empty($decision['decision_maker'])
            && ! empty($decision['authority_basis'])
            && $decidedAt !== null;
        $hasDecisionEvidence = $evidenceKey !== '' && in_array($evidenceKey, $evidenceKeys, true);
        $hasValidPeriod = $validUntil !== null
            && $decidedAt !== null
            && $validUntil->greaterThan($decidedAt)
            && ! $isPastValidity;
        $hasValidOutcomeTerms = match ($outcome) {
            AcceptanceOutcome::Accepted => $conditions === [],
            AcceptanceOutcome::AcceptedWithConditions => $conditions !== [],
            default => false,
        };
        $permitsEngagementConsideration = $outcome?->permitsEngagementConsideration() === true
            && $reviewStatus === AcceptanceReviewStatus::DecisionRecorded
            && $governingPolicyOperative
            && ! $hasUnresolvedAssessment
            && ! empty($decision['risk_classification'])
            && $hasCompleteAuthority
            && $hasDecisionEvidence
            && $hasValidPeriod
            && $hasValidOutcomeTerms;

        return [
            ...$decision,
            'outcome_label' => $outcome?->label() ?? 'Invalid',
            'permits_engagement_consideration' => $permitsEngagementConsideration,
            'temporal_state' => $isPastValidity ? 'past_validity' : 'within_validity',
        ];
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{code: string, message: string}
     */
    private function issue(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }
}
