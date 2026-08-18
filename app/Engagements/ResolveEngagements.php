<?php

namespace App\Engagements;

use App\ClientAcceptance\ResolvedClientAcceptance;
use App\Partnership\ResolvedPartnership;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveEngagements
{
    public function handle(
        EngagementDefinition $definition,
        ResolvedClientAcceptance $clientAcceptance,
        ResolvedPartnership $partnership,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedEngagements {
        $conflicts = [];
        $decisionGaps = [];
        $evidenceGaps = [];
        $readinessGaps = [];
        $lifecycleCounts = array_fill_keys(array_map(
            static fn (EngagementLifecycleStatus $status): string => $status->value,
            EngagementLifecycleStatus::cases(),
        ), 0);

        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $this->validateOpeningStandard($definition->openingRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidenceRecords($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $policiesOperative] = $this->resolveGoverningPolicies(
            $definition,
            $policyRegistry,
            $effectiveAt,
            $conflicts,
            $readinessGaps,
        );
        $acceptedClients = $this->acceptedClients($clientAcceptance);
        $partners = $this->partners($partnership);
        $resolvedEngagements = [];
        $engagementKeys = [];
        foreach ($definition->engagements as $engagement) {
            $resolvedEngagements[] = $this->resolveEngagement(
                engagement: $engagement,
                policiesOperative: $policiesOperative,
                acceptedClients: $acceptedClients,
                partners: $partners,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                engagementKeys: $engagementKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedEngagements(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            openingRequirements: $definition->openingRequirements,
            engagements: $resolvedEngagements,
            evidenceRecords: $definition->evidenceRecords,
            lifecycleCounts: $lifecycleCounts,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
            readinessGaps: $readinessGaps,
        );
    }

    /**
     * @param  list<array{key: string, label: string, question: string}>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateOpeningStandard(array $requirements, array &$conflicts): void
    {
        $keys = [];

        foreach ($requirements as $requirement) {
            if (
                $requirement['key'] === ''
                || in_array($requirement['key'], $keys, true)
                || $requirement['label'] === ''
                || $requirement['question'] === ''
            ) {
                $conflicts[] = $this->issue('invalid_opening_requirement', 'An Engagement Opening requirement is missing, incomplete, or duplicated.');
            }

            $keys[] = $requirement['key'];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<string>
     */
    private function validateEvidenceRecords(array $records, array &$conflicts, array &$evidenceGaps): array
    {
        $keys = [];
        $validKeys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $unique = $key !== '' && ! in_array($key, $keys, true);

            if (! $unique) {
                $conflicts[] = $this->issue('invalid_engagement_evidence_key', 'An Engagement Evidence Record has a missing or duplicate key.');
            }

            $complete = ! (
                empty($record['record_type'])
                || empty($record['subject'])
                || empty($record['actor'])
                || empty($record['recorded_at'])
                || empty($record['source'])
                || empty($record['reason'])
                || empty($record['state'])
            );

            if (! $complete) {
                $evidenceGaps[] = $this->issue('incomplete_engagement_evidence_record', "Evidence Record {$key} is incomplete.");
            }

            $keys[] = $key;

            if ($unique && $complete) {
                $validKeys[] = $key;
            }
        }

        return $validKeys;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $readinessGaps
     * @return array{list<array<string, mixed>>, bool}
     */
    private function resolveGoverningPolicies(
        EngagementDefinition $definition,
        ResolvedPolicyRegistry $policyRegistry,
        Carbon $asOf,
        array &$conflicts,
        array &$readinessGaps,
    ): array {
        $resolved = [];
        $allRequiredOperative = true;

        foreach ($definition->governingPolicies as $reference) {
            $policy = collect($policyRegistry->policies)->firstWhere('key', $reference['key']);
            $version = ! is_array($policy)
                ? null
                : $this->findPolicyVersion($policy['versions'] ?? null, $reference['version']);

            if (! is_array($policy) || ! is_array($version)) {
                $conflicts[] = $this->issue('missing_engagement_governing_policy', "Engagement Opening references missing policy {$reference['key']} version {$reference['version']}.");
                $operative = false;
                $status = null;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $approvalEvidenceKey = is_array($approval) ? ($approval['evidence_record_key'] ?? null) : null;
                $policyEffectiveAt = $this->date($version['effective_at'] ?? null);
                $evidenceKeys = array_column($policyRegistry->evidenceRecords, 'key');
                $operative = ($version['operative'] ?? false) === true || ($status === PolicyLifecycleStatus::Effective
                    && ($version['content_integrity'] ?? null) === 'verified'
                    && $policyEffectiveAt !== null
                    && $policyEffectiveAt->lessThanOrEqualTo($asOf)
                    && is_array($approval)
                    && ($approval['outcome'] ?? null) === 'approved'
                    && ! empty($approval['approver'])
                    && ! empty($approval['authority_basis'])
                    && ! empty($approval['decided_at'])
                    && is_string($approvalEvidenceKey)
                    && in_array($approvalEvidenceKey, $evidenceKeys, true));
            }

            if ($reference['required_for_opening'] && ! $operative) {
                $allRequiredOperative = false;
                $policyTitle = is_array($policy) ? (string) $policy['title'] : $reference['key'];
                $readinessGaps[] = $this->issue(
                    'engagement_governing_policy_not_effective',
                    "{$policyTitle} version {$reference['version']} is not Effective.",
                );
            }

            $resolved[] = [
                ...$reference,
                'title' => is_array($policy) ? $policy['title'] : 'Unknown Policy',
                'status' => $status instanceof PolicyLifecycleStatus ? $status->value : 'missing',
                'status_label' => $status instanceof PolicyLifecycleStatus ? $status->label() : 'Missing',
                'operative' => $operative,
            ];
        }

        return [$resolved, $allRequiredOperative];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findPolicyVersion(mixed $versions, string $versionNumber): ?array
    {
        if (! is_array($versions)) {
            return null;
        }

        foreach ($versions as $version) {
            if (is_array($version) && ($version['version'] ?? null) === $versionNumber) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function acceptedClients(ResolvedClientAcceptance $clientAcceptance): array
    {
        $clients = [];

        foreach ($clientAcceptance->prospectiveClients as $client) {
            if (
                ($client['institutional_status'] ?? null) === 'accepted_client'
                && ($client['decision']['permits_engagement_consideration'] ?? false) === true
            ) {
                $clients[(string) $client['key']] = (string) $client['legal_name'];
            }
        }

        return $clients;
    }

    /**
     * @return array<string, string>
     */
    private function partners(ResolvedPartnership $partnership): array
    {
        $partners = [];

        foreach ($partnership->projections['partnership'] as $partner) {
            $partners[(string) $partner['key']] = (string) $partner['name'];
        }

        return $partners;
    }

    /**
     * @param  array<string, mixed>  $engagement
     * @param  array<string, string>  $acceptedClients
     * @param  array<string, string>  $partners
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $engagementKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveEngagement(
        array $engagement,
        bool $policiesOperative,
        array $acceptedClients,
        array $partners,
        array $evidenceKeys,
        Carbon $asOf,
        array &$engagementKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $conflictCount = count($conflicts);
        $decisionGapCount = count($decisionGaps);
        $evidenceGapCount = count($evidenceGaps);
        $key = (string) ($engagement['key'] ?? '');
        $title = (string) ($engagement['title'] ?? $key);
        $status = EngagementLifecycleStatus::tryFrom((string) ($engagement['lifecycle_status'] ?? ''));

        if ($key === '' || in_array($key, $engagementKeys, true)) {
            $conflicts[] = $this->issue('invalid_engagement_key', 'An Engagement has a missing or duplicate key.');
        }

        $engagementKeys[] = $key;

        if ($status === null) {
            $conflicts[] = $this->issue('invalid_engagement_lifecycle_status', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }

        if ($title === '') {
            $decisionGaps[] = $this->issue('missing_engagement_title', "Engagement {$key} has no title.");
        }

        $clientKey = (string) ($engagement['client_key'] ?? '');
        $clientAccepted = isset($acceptedClients[$clientKey]);

        if (! $clientAccepted) {
            $decisionGaps[] = $this->issue('client_not_accepted_for_engagement', "{$title} does not reference a currently accepted Client.");
        }

        [$responsiblePartner, $hasExactlyOneResponsiblePartner] = $this->resolveResponsiblePartner(
            $title,
            $engagement['responsible_partner_assignments'] ?? [],
            $partners,
            $evidenceKeys,
            $asOf,
            $decisionGaps,
            $evidenceGaps,
        );
        $scopeComplete = $this->validateScope($title, $engagement['scope'] ?? null, $decisionGaps);
        $mandateValid = $this->validateClientMandate(
            $title,
            $engagement['client_mandate'] ?? null,
            $evidenceKeys,
            $asOf,
            $decisionGaps,
            $evidenceGaps,
        );
        $riskAccepted = $this->validateRisk(
            $title,
            $engagement['risk'] ?? null,
            $evidenceKeys,
            $decisionGaps,
            $evidenceGaps,
        );
        $operatingTermsComplete = $this->validateOpeningTerms($title, $engagement, $asOf, $decisionGaps);
        [$approvalPermitsOpening, $approvalTime] = $this->validateApproval(
            $title,
            $engagement['approval'] ?? null,
            $evidenceKeys,
            $decisionGaps,
            $evidenceGaps,
        );
        $openingComplete = $this->validateOpeningRecord(
            $title,
            $engagement['opening'] ?? null,
            $status,
            $approvalTime,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );

        $hasNoRecordIssues = count($conflicts) === $conflictCount
            && count($decisionGaps) === $decisionGapCount
            && count($evidenceGaps) === $evidenceGapCount;
        $mayPerformClientWork = $status === EngagementLifecycleStatus::Open
            && $policiesOperative
            && $clientAccepted
            && $hasExactlyOneResponsiblePartner
            && $scopeComplete
            && $mandateValid
            && $riskAccepted
            && $operatingTermsComplete
            && $approvalPermitsOpening
            && $openingComplete
            && $hasNoRecordIssues;

        if ($status === EngagementLifecycleStatus::Open && ! $mayPerformClientWork) {
            $conflicts[] = $this->issue('open_engagement_without_complete_gate', "{$title} is marked Open without satisfying every Engagement Opening gate.");
        }

        return [
            ...$engagement,
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'client_name' => $acceptedClients[$clientKey] ?? null,
            'responsible_partner' => $responsiblePartner,
            'may_perform_client_work' => $mayPerformClientWork,
            'operational_status' => match (true) {
                $mayPerformClientWork => 'open_engagement',
                $status === EngagementLifecycleStatus::Open => 'blocked_opening',
                $status === EngagementLifecycleStatus::Approved => 'approved_not_open',
                $status === EngagementLifecycleStatus::Suspended => 'suspended',
                $status === EngagementLifecycleStatus::Closed => 'closed',
                default => 'pending',
            },
        ];
    }

    /**
     * @param  array<string, string>  $partners
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{array<string, mixed>|null, bool}
     */
    private function resolveResponsiblePartner(
        string $title,
        mixed $assignments,
        array $partners,
        array $evidenceKeys,
        Carbon $asOf,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        if (! is_array($assignments)) {
            $decisionGaps[] = $this->issue('invalid_responsible_partner_history', "{$title} has an invalid Responsible Partner history.");

            return [null, false];
        }

        $current = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment)) {
                continue;
            }

            $effectiveFrom = $this->date($assignment['effective_from'] ?? null);
            $effectiveUntil = $this->date($assignment['effective_until'] ?? null);

            if ($effectiveFrom !== null && $effectiveFrom->lessThanOrEqualTo($asOf) && ($effectiveUntil === null || $effectiveUntil->greaterThan($asOf))) {
                $current[] = $assignment;
            }
        }

        if (count($current) !== 1) {
            $decisionGaps[] = $this->issue('responsible_partner_not_singular', "{$title} must have exactly one current Responsible Partner.");

            return [null, false];
        }

        $assignment = $current[0];
        $partnerKey = (string) ($assignment['partner_key'] ?? '');

        if (! isset($partners[$partnerKey])) {
            $decisionGaps[] = $this->issue('unknown_responsible_partner', "{$title} assigns responsibility to an unknown Partner.");

            return [null, false];
        }

        if ($this->missingEvidence($assignment['evidence_record_key'] ?? null, $evidenceKeys)) {
            $evidenceGaps[] = $this->issue('missing_responsible_partner_evidence', "{$title} has no known Evidence Record for its current Responsible Partner assignment.");

            return [null, false];
        }

        return [[...$assignment, 'partner_name' => $partners[$partnerKey]], true];
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateScope(string $title, mixed $scope, array &$decisionGaps): bool
    {
        $complete = is_array($scope)
            && ! empty($scope['purpose'])
            && ! empty($scope['services'])
            && ! empty($scope['deliverables'])
            && ! empty($scope['exclusions']);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_engagement_scope', "{$title} does not define purpose, services, deliverables, and exclusions.");
        }

        return $complete;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateClientMandate(
        string $title,
        mixed $mandate,
        array $evidenceKeys,
        Carbon $asOf,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        if (! is_array($mandate)) {
            $decisionGaps[] = $this->issue('missing_client_mandate', "{$title} has no explicit Client Mandate.");

            return false;
        }

        $grantedAt = $this->date($mandate['granted_at'] ?? null);
        $validUntil = $this->date($mandate['valid_until'] ?? null);
        $complete = ! empty($mandate['grantor'])
            && ! empty($mandate['authority_basis'])
            && $grantedAt !== null
            && $validUntil !== null
            && ! empty($mandate['authorized_requestors'])
            && ! empty($mandate['environments'])
            && ! empty($mandate['systems'])
            && ! empty($mandate['permitted_actions']);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_client_mandate', "{$title} has an incomplete Client Mandate or operating boundary.");
        }

        $current = $grantedAt !== null
            && $validUntil !== null
            && $grantedAt->lessThanOrEqualTo($asOf)
            && $validUntil->greaterThan($asOf)
            && $validUntil->greaterThan($grantedAt);

        if ($complete && ! $current) {
            $decisionGaps[] = $this->issue('client_mandate_not_current', "{$title} has a Client Mandate that is not currently valid.");
        }

        $evidenced = ! $this->missingEvidence($mandate['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_client_mandate_evidence', "{$title} Client Mandate is not linked to a known Evidence Record.");
        }

        return $complete && $current && $evidenced;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateRisk(
        string $title,
        mixed $risk,
        array $evidenceKeys,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $complete = is_array($risk)
            && ! empty($risk['classification'])
            && ! empty($risk['summary'])
            && ! empty($risk['owner'])
            && ! empty($risk['accepted_by'])
            && ! empty($risk['authority_basis'])
            && $this->date($risk['accepted_at'] ?? null) !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_engagement_risk', "{$title} does not record its risk classification, owner, acceptance, and authority.");
        }

        $evidenced = is_array($risk)
            && ! $this->missingEvidence($risk['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_engagement_risk_evidence', "{$title} risk acceptance is not linked to a known Evidence Record.");
        }

        return $complete && $evidenced;
    }

    /**
     * @param  array<string, mixed>  $engagement
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateOpeningTerms(
        string $title,
        array $engagement,
        Carbon $asOf,
        array &$decisionGaps,
    ): bool {
        $commercial = $engagement['commercial_terms'] ?? null;
        $term = $engagement['term'] ?? null;
        $operating = $engagement['operating_terms'] ?? null;
        $roles = $engagement['roles'] ?? null;
        $commencementAt = is_array($term) ? $this->date($term['commencement_at'] ?? null) : null;
        $endAt = is_array($term) ? $this->date($term['end_at'] ?? null) : null;
        $complete = is_array($commercial)
            && ! empty($commercial['pricing_basis'])
            && ! empty($commercial['billing_basis'])
            && ! empty($commercial['liability_position'])
            && is_array($term)
            && $commencementAt !== null
            && $endAt !== null
            && $endAt->greaterThan($commencementAt)
            && ! empty($term['termination'])
            && ! empty($term['transition'])
            && is_array($operating)
            && ! empty($operating['client_responsibilities'])
            && ! empty($operating['firm_responsibilities'])
            && ! empty($operating['data_classification'])
            && ! empty($operating['asset_ownership'])
            && ! empty($operating['approved_access'])
            && ! empty($operating['change_authority'])
            && ! empty($operating['incident_authority'])
            && ! empty($operating['escalation_contacts'])
            && ! empty($operating['evidence_requirements'])
            && is_array($roles)
            && ! empty($roles['practice'])
            && ! empty($roles['technical_lead'])
            && ! empty($roles['service_team']);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_engagement_terms', "{$title} has incomplete commercial, term, role, or operating boundaries.");
        }

        $termCurrent = $commencementAt !== null
            && $endAt !== null
            && $commencementAt->lessThanOrEqualTo($asOf)
            && $endAt->greaterThan($asOf);

        if ($complete && ! $termCurrent) {
            $decisionGaps[] = $this->issue('engagement_term_not_current', "{$title} is outside its recorded effective period.");
        }

        return $complete && $termCurrent;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateApproval(
        string $title,
        mixed $approval,
        array $evidenceKeys,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        if (! is_array($approval)) {
            $decisionGaps[] = $this->issue('missing_engagement_approval', "{$title} has no explicit Engagement Approval.");

            return [false, null];
        }

        $outcome = EngagementApprovalOutcome::tryFrom((string) ($approval['outcome'] ?? ''));
        $decidedAt = $this->date($approval['decided_at'] ?? null);
        $conditions = is_array($approval['conditions'] ?? null) ? $approval['conditions'] : [];
        $complete = $outcome !== null
            && ! empty($approval['approver'])
            && ! empty($approval['authority_basis'])
            && $decidedAt !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_engagement_approval', "{$title} has an incomplete approval decision or authority record.");
        }

        if ($outcome === EngagementApprovalOutcome::ApprovedWithConditions && $conditions === []) {
            $decisionGaps[] = $this->issue('missing_engagement_approval_conditions', "{$title} is approved with conditions but records none.");
            $complete = false;
        }

        if ($outcome === EngagementApprovalOutcome::Approved && $conditions !== []) {
            $decisionGaps[] = $this->issue('unclassified_engagement_conditions', "{$title} carries conditions but is not classified as conditionally approved.");
            $complete = false;
        }

        if ($outcome === EngagementApprovalOutcome::Rejected && empty($approval['reason'])) {
            $decisionGaps[] = $this->issue('missing_engagement_rejection_reason', "{$title} is rejected without a reason.");
        }

        $evidenced = ! $this->missingEvidence($approval['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_engagement_approval_evidence', "{$title} approval is not linked to a known Evidence Record.");
        }

        $outcomePermitsOpening = $outcome instanceof EngagementApprovalOutcome
            && $outcome->permitsOpening();

        return [$complete && $evidenced && $outcomePermitsOpening, $decidedAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateOpeningRecord(
        string $title,
        mixed $opening,
        ?EngagementLifecycleStatus $status,
        ?Carbon $approvalTime,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        if ($status !== EngagementLifecycleStatus::Open) {
            if (is_array($opening)) {
                $conflicts[] = $this->issue('opening_execution_without_open_status', "{$title} records opening execution without an Open lifecycle status.");
            }

            return false;
        }

        if (! is_array($opening)) {
            $decisionGaps[] = $this->issue('missing_engagement_opening_record', "{$title} is marked Open without an Opening Record.");

            return false;
        }

        $openedAt = $this->date($opening['opened_at'] ?? null);
        $complete = ! empty($opening['opened_by'])
            && ! empty($opening['authority_basis'])
            && ! empty($opening['verification'])
            && $openedAt !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_engagement_opening_record', "{$title} has an incomplete Opening Record or verification.");
        }

        if ($openedAt !== null && ($approvalTime === null || $openedAt->lessThan($approvalTime))) {
            $conflicts[] = $this->issue('engagement_opened_before_approval', "{$title} was opened before its recorded approval.");
            $complete = false;
        }

        $evidenced = ! $this->missingEvidence($opening['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_engagement_opening_evidence', "{$title} Opening Record is not linked to a known Evidence Record.");
        }

        return $complete && $evidenced;
    }

    /**
     * @param  list<string>  $evidenceKeys
     */
    private function missingEvidence(mixed $reference, array $evidenceKeys): bool
    {
        return ! is_string($reference) || $reference === '' || ! in_array($reference, $evidenceKeys, true);
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
