<?php

namespace App\Changes;

use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use App\ProductionAccess\ResolvedProductionAccess;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveChanges
{
    public function handle(
        ChangeDefinition $definition,
        ResolvedEngagements $engagements,
        ResolvedProductionAccess $productionAccess,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedChanges {
        $conflicts = [];
        $decisionGaps = [];
        $evidenceGaps = [];
        $readinessGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $lifecycleCounts = array_fill_keys(array_map(
            static fn (ChangeLifecycleStatus $status): string => $status->value,
            ChangeLifecycleStatus::cases(),
        ), 0);

        $this->validateRecordStandard($definition->recordRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidenceRecords($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $policiesOperative] = $this->resolveGoverningPolicies(
            $definition,
            $policyRegistry,
            $effectiveAt,
            $conflicts,
            $readinessGaps,
        );
        $openEngagements = $this->openEngagements($engagements);
        $activeAccessGrants = $this->activeAccessGrants($productionAccess);
        $activePolicyExceptions = $this->activePolicyExceptions($policyRegistry);
        $changeKeys = [];
        $resolvedChanges = [];

        foreach ($definition->changeRecords as $change) {
            $resolvedChanges[] = $this->resolveChange(
                change: $change,
                policiesOperative: $policiesOperative,
                openEngagements: $openEngagements,
                activeAccessGrants: $activeAccessGrants,
                activePolicyExceptions: $activePolicyExceptions,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                changeKeys: $changeKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedChanges(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            recordRequirements: $definition->recordRequirements,
            changeRecords: $resolvedChanges,
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
    private function validateRecordStandard(array $requirements, array &$conflicts): void
    {
        $keys = [];

        foreach ($requirements as $requirement) {
            if (
                $requirement['key'] === ''
                || in_array($requirement['key'], $keys, true)
                || $requirement['label'] === ''
                || $requirement['question'] === ''
            ) {
                $conflicts[] = $this->issue('invalid_change_requirement', 'A Change Record requirement is missing, incomplete, or duplicated.');
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
                $conflicts[] = $this->issue('invalid_change_evidence_key', 'A Change Evidence Record has a missing or duplicate key.');
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
                $evidenceGaps[] = $this->issue('incomplete_change_evidence_record', "Evidence Record {$key} is incomplete.");
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
        ChangeDefinition $definition,
        ResolvedPolicyRegistry $policyRegistry,
        Carbon $asOf,
        array &$conflicts,
        array &$readinessGaps,
    ): array {
        $resolved = [];
        $allRequiredOperative = true;
        $policyEvidenceKeys = array_column($policyRegistry->evidenceRecords, 'key');

        foreach ($definition->governingPolicies as $reference) {
            $policy = collect($policyRegistry->policies)->firstWhere('key', $reference['key']);
            $version = ! is_array($policy)
                ? null
                : $this->findPolicyVersion($policy['versions'] ?? null, $reference['version']);

            if (! is_array($policy) || ! is_array($version)) {
                $conflicts[] = $this->issue('missing_change_governing_policy', "Change Management references missing policy {$reference['key']} version {$reference['version']}.");
                $operative = false;
                $status = null;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $approvalEvidenceKey = is_array($approval) ? ($approval['evidence_record_key'] ?? null) : null;
                $policyEffectiveAt = $this->date($version['effective_at'] ?? null);
                $operative = $status === PolicyLifecycleStatus::Effective
                    && ($version['content_integrity'] ?? null) === 'verified'
                    && $policyEffectiveAt !== null
                    && $policyEffectiveAt->lessThanOrEqualTo($asOf)
                    && is_array($approval)
                    && ($approval['outcome'] ?? null) === 'approved'
                    && ! empty($approval['approver'])
                    && ! empty($approval['authority_basis'])
                    && ! empty($approval['decided_at'])
                    && is_string($approvalEvidenceKey)
                    && in_array($approvalEvidenceKey, $policyEvidenceKeys, true);
            }

            if ($reference['required_for_execution'] && ! $operative) {
                $allRequiredOperative = false;
                $policyTitle = is_array($policy) ? (string) $policy['title'] : $reference['key'];
                $readinessGaps[] = $this->issue(
                    'change_governing_policy_not_effective',
                    "{$policyTitle} version {$reference['version']} is not Effective and fully evidenced.",
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

    /** @return array<string, mixed>|null */
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

    /** @return array<string, array<string, mixed>> */
    private function openEngagements(ResolvedEngagements $engagements): array
    {
        $open = [];

        foreach ($engagements->engagements as $engagement) {
            if (($engagement['operational_status'] ?? null) === 'open_engagement' && ($engagement['may_perform_client_work'] ?? false) === true) {
                $open[(string) $engagement['key']] = $engagement;
            }
        }

        return $open;
    }

    /** @return array<string, array<string, mixed>> */
    private function activeAccessGrants(ResolvedProductionAccess $productionAccess): array
    {
        $active = [];

        foreach ($productionAccess->accessGrants as $grant) {
            if (($grant['may_use_access'] ?? false) === true && ($grant['operational_status'] ?? null) === 'active_authority') {
                $active[(string) $grant['key']] = $grant;
            }
        }

        return $active;
    }

    /** @return array<string, array<string, mixed>> */
    private function activePolicyExceptions(ResolvedPolicyRegistry $policyRegistry): array
    {
        $active = [];

        foreach ($policyRegistry->exceptions as $exception) {
            if (($exception['status'] ?? null) === 'active' && ($exception['temporal_state'] ?? null) === 'within_term') {
                $active[(string) $exception['key']] = $exception;
            }
        }

        return $active;
    }

    /**
     * @param  array<string, mixed>  $change
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  array<string, array<string, mixed>>  $activeAccessGrants
     * @param  array<string, array<string, mixed>>  $activePolicyExceptions
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $changeKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveChange(
        array $change,
        bool $policiesOperative,
        array $openEngagements,
        array $activeAccessGrants,
        array $activePolicyExceptions,
        array $evidenceKeys,
        Carbon $asOf,
        array &$changeKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $conflictCount = count($conflicts);
        $decisionGapCount = count($decisionGaps);
        $evidenceGapCount = count($evidenceGaps);
        $key = (string) ($change['key'] ?? '');
        $title = (string) ($change['title'] ?? $key);
        $status = ChangeLifecycleStatus::tryFrom((string) ($change['lifecycle_status'] ?? ''));
        $type = ChangeType::tryFrom((string) ($change['change_type'] ?? ''));

        if ($key === '' || in_array($key, $changeKeys, true)) {
            $conflicts[] = $this->issue('invalid_change_key', 'A Change Record has a missing or duplicate key.');
        }

        $changeKeys[] = $key;

        if ($status === null) {
            $conflicts[] = $this->issue('invalid_change_status', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }

        if ($type === null) {
            $conflicts[] = $this->issue('invalid_change_type', "{$title} has an invalid Change classification.");
        }

        $requestValid = $this->validateRequest($title, $change['request'] ?? null, $evidenceKeys, $decisionGaps, $evidenceGaps);
        [$engagement, $mandateValid] = $this->validateEngagementAndMandate(
            $title,
            $change['engagement_key'] ?? null,
            $change['scope'] ?? null,
            $openEngagements,
            $decisionGaps,
        );
        $scopeValid = $this->validateScope($title, $change['scope'] ?? null, $decisionGaps);
        $classificationValid = $this->validateClassification($title, $type, $change['classification'] ?? null, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $riskValid = $this->validateRisk($title, $change['risk'] ?? null, $decisionGaps);
        [$technicalReviewValid, $reviewedAt] = $this->validateTechnicalReview(
            $title,
            $change['technical_review'] ?? null,
            $status,
            $evidenceKeys,
            $decisionGaps,
            $evidenceGaps,
        );
        $recoveryValid = $this->validateRecovery($title, $change['recovery'] ?? null, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $exceptionsValid = $this->validatePolicyExceptions(
            $title,
            $change['policy_exception_keys'] ?? null,
            $activePolicyExceptions,
            $decisionGaps,
        );
        [$approvalsValid, $latestApprovalAt] = $this->validateApprovals(
            $title,
            $change['approvals'] ?? null,
            $type,
            $change['risk'] ?? null,
            $status,
            $reviewedAt,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        [$scheduleValid, $windowStartsAt, $windowEndsAt] = $this->validateSchedule(
            $title,
            $change['schedule'] ?? null,
            $status,
            $latestApprovalAt,
            $asOf,
            $conflicts,
            $decisionGaps,
        );
        [$accessGrant, $executorValid] = $this->validateExecutorAccess(
            $title,
            $change['executor'] ?? null,
            $change['access_grant_key'] ?? null,
            $change['engagement_key'] ?? null,
            $change['scope'] ?? null,
            $status,
            $activeAccessGrants,
            $decisionGaps,
        );
        [$executionValid, $executionStartedAt, $executionCompletedAt] = $this->validateExecution(
            $title,
            $change['execution'] ?? null,
            $status,
            $latestApprovalAt,
            $windowStartsAt,
            $windowEndsAt,
            $change['executor'] ?? null,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $verificationValid = $this->validateVerification(
            $title,
            $change['verification'] ?? null,
            $status,
            $change['risk'] ?? null,
            $executionCompletedAt,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $outcomeValid = $this->validateOutcome(
            $title,
            $status,
            $type,
            $change['outcome'] ?? null,
            $change['communication'] ?? null,
            $change['post_implementation_review'] ?? null,
            $change['closure'] ?? null,
            $executionStartedAt,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );

        $hasNoRecordIssues = count($conflicts) === $conflictCount
            && count($decisionGaps) === $decisionGapCount
            && count($evidenceGaps) === $evidenceGapCount;
        $withinExecutionWindow = $windowStartsAt !== null
            && $windowEndsAt !== null
            && $windowStartsAt->lessThanOrEqualTo($asOf)
            && $windowEndsAt->greaterThan($asOf);
        $mayExecuteChange = $status === ChangeLifecycleStatus::Scheduled
            && $policiesOperative
            && $requestValid
            && $engagement !== null
            && $mandateValid
            && $scopeValid
            && $classificationValid
            && $riskValid
            && $technicalReviewValid
            && $recoveryValid
            && $exceptionsValid
            && $approvalsValid
            && $scheduleValid
            && $withinExecutionWindow
            && $executorValid
            && $hasNoRecordIssues;

        if ($status === ChangeLifecycleStatus::Scheduled && ! $mayExecuteChange) {
            $conflicts[] = $this->issue('scheduled_change_without_complete_gate', "{$title} is Scheduled without satisfying every execution gate.");
        }

        return [
            ...$change,
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'change_type_label' => $type?->label() ?? 'Invalid',
            'engagement_title' => is_array($engagement) ? $engagement['title'] ?? null : null,
            'client_name' => is_array($engagement) ? $engagement['client_name'] ?? null : null,
            'access_grant_title' => is_array($accessGrant) ? $accessGrant['title'] ?? null : null,
            'may_execute_change' => $mayExecuteChange,
            'window_state' => match (true) {
                $windowStartsAt !== null && $windowStartsAt->greaterThan($asOf) => 'before_window',
                $windowEndsAt !== null && $windowEndsAt->lessThanOrEqualTo($asOf) => 'after_window',
                $withinExecutionWindow => 'within_window',
                default => 'undefined',
            },
            'operational_status' => match (true) {
                $mayExecuteChange => 'authorized_for_execution',
                $status === ChangeLifecycleStatus::Scheduled => 'blocked_scheduled_change',
                $status === ChangeLifecycleStatus::Approved => 'approved_not_scheduled',
                $status === ChangeLifecycleStatus::Executing => 'execution_in_progress',
                $status === ChangeLifecycleStatus::Verifying => 'awaiting_verification',
                $status === ChangeLifecycleStatus::Closed && $outcomeValid && $verificationValid && $executionValid => 'closed_verified',
                $status === ChangeLifecycleStatus::Failed => 'failed',
                $status === ChangeLifecycleStatus::RolledBack => 'rolled_back',
                $status === ChangeLifecycleStatus::Cancelled => 'cancelled',
                $status === ChangeLifecycleStatus::Rejected => 'rejected',
                default => 'pending',
            },
        ];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateRequest(string $title, mixed $request, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $complete = is_array($request)
            && ! empty($request['requested_by'])
            && ! empty($request['requested_at'])
            && ! empty($request['rationale'])
            && ! empty($request['desired_outcome'])
            && ! empty($request['source_reference']);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_change_request', "{$title} has an incomplete request, rationale, outcome, or source reference.");
        }

        $evidenced = is_array($request) && ! $this->missingEvidence($request['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_change_request_evidence', "{$title} request is not linked to known evidence.");
        }

        return $complete && $evidenced;
    }

    /**
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{array<string, mixed>|null, bool}
     */
    private function validateEngagementAndMandate(string $title, mixed $engagementKey, mixed $scope, array $openEngagements, array &$decisionGaps): array
    {
        $engagement = is_string($engagementKey) ? ($openEngagements[$engagementKey] ?? null) : null;

        if (! is_array($engagement)) {
            $decisionGaps[] = $this->issue('change_without_open_engagement', "{$title} does not reference a current Open Engagement.");

            return [null, false];
        }

        $mandate = $engagement['client_mandate'] ?? null;
        $valid = is_array($scope)
            && is_array($mandate)
            && ($scope['client_key'] ?? null) === ($engagement['client_key'] ?? null)
            && in_array($scope['system'] ?? null, $mandate['systems'] ?? [], true)
            && in_array($scope['environment'] ?? null, $mandate['environments'] ?? [], true)
            && in_array($scope['client_mandate_action'] ?? null, $mandate['permitted_actions'] ?? [], true);

        if (! $valid) {
            $decisionGaps[] = $this->issue('change_outside_client_mandate', "{$title} is outside the Engagement Client Mandate.");
        }

        return [$engagement, $valid];
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateScope(string $title, mixed $scope, array &$decisionGaps): bool
    {
        $valid = is_array($scope)
            && ! empty($scope['client_key'])
            && ! empty($scope['system'])
            && ! empty($scope['environment'])
            && ! empty($scope['service'])
            && ! empty($scope['components'])
            && ! empty($scope['implementation_plan'])
            && ! empty($scope['expected_outcome'])
            && ! empty($scope['client_mandate_action'])
            && is_array($scope['excluded_actions'] ?? null);

        if (! $valid) {
            $decisionGaps[] = $this->issue('incomplete_change_scope', "{$title} has an incomplete target, implementation plan, expected outcome, or exclusion boundary.");
        }

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateClassification(string $title, ?ChangeType $type, mixed $classification, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $valid = is_array($classification)
            && $type !== null
            && ($classification['classified_as'] ?? null) === $type->value
            && ! empty($classification['classified_by'])
            && ! empty($classification['authority_basis'])
            && ! empty($classification['reason']);

        if ($type === ChangeType::Standard) {
            $standard = is_array($classification) ? ($classification['standard_definition'] ?? null) : null;
            $valid = $valid
                && is_array($standard)
                && ! empty($standard['key'])
                && ! empty($standard['version'])
                && ($standard['current'] ?? false) === true
                && ($standard['eligibility_confirmed'] ?? false) === true
                && ! empty($standard['review_at']);
        }

        if ($type === ChangeType::Emergency) {
            $valid = $valid
                && ! empty($classification['emergency_reason'])
                && ($classification['delay_increases_material_harm'] ?? false) === true;
        }

        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_change_classification', "{$title} has an incomplete or inconsistent Change classification.");
        }

        $evidenced = is_array($classification) && ! $this->missingEvidence($classification['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_change_classification_evidence', "{$title} classification is not linked to known evidence.");
        }

        return $valid && $evidenced;
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateRisk(string $title, mixed $risk, array &$decisionGaps): bool
    {
        $valid = is_array($risk)
            && in_array($risk['classification'] ?? null, ['low', 'moderate', 'high', 'critical'], true)
            && ! empty($risk['likelihood'])
            && ! empty($risk['impact'])
            && ! empty($risk['affected_services'])
            && ! empty($risk['client_impact'])
            && ! empty($risk['risk_owner'])
            && ! empty($risk['controls'])
            && ! empty($risk['residual_risk']);

        if (! $valid) {
            $decisionGaps[] = $this->issue('incomplete_change_risk', "{$title} has an incomplete risk, impact, ownership, control, or residual-risk record.");
        }

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateTechnicalReview(string $title, mixed $review, ?ChangeLifecycleStatus $status, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): array
    {
        $required = ! in_array($status, [ChangeLifecycleStatus::Requested, ChangeLifecycleStatus::Cancelled], true);

        if (! is_array($review)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_change_technical_review', "{$title} has no Technical Review Record.");
            }

            return [false, null];
        }

        $reviewedAt = $this->date($review['reviewed_at'] ?? null);
        $complete = ! empty($review['reviewed_by'])
            && ! empty($review['competence_basis'])
            && ($review['implementation_reviewed'] ?? false) === true
            && ($review['dependencies_reviewed'] ?? false) === true
            && ($review['tests_reviewed'] ?? false) === true
            && ($review['monitoring_reviewed'] ?? false) === true
            && ($review['recovery_reviewed'] ?? false) === true
            && in_array($review['outcome'] ?? null, ['recommended', 'not_recommended'], true)
            && $reviewedAt !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_change_technical_review', "{$title} has an incomplete Technical Review Record.");
        }

        $evidenced = ! $this->missingEvidence($review['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_change_technical_review_evidence', "{$title} technical review is not linked to known evidence.");
        }

        return [$required && $complete && $evidenced && ($review['outcome'] ?? null) === 'recommended', $reviewedAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateRecovery(string $title, mixed $recovery, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $backup = is_array($recovery) ? ($recovery['backup_confirmation'] ?? null) : null;
        $valid = is_array($recovery)
            && ($recovery['viable'] ?? false) === true
            && ! empty($recovery['strategy'])
            && ! empty($recovery['steps'])
            && ! empty($recovery['triggers'])
            && ! empty($recovery['owner'])
            && ! empty($recovery['estimated_recovery_time'])
            && ($recovery['irreversible'] ?? true) === false
            && is_array($backup)
            && ($backup['required'] ?? false) === true
            && ($backup['confirmed'] ?? false) === true
            && ! empty($backup['recovery_point'])
            && ! empty($backup['confirmed_by'])
            && ! empty($backup['confirmed_at']);

        if (! $valid) {
            $decisionGaps[] = $this->issue('production_change_without_recovery', "{$title} lacks a viable recovery plan and confirmed recovery point.");
        }

        $planEvidenced = is_array($recovery) && ! $this->missingEvidence($recovery['evidence_record_key'] ?? null, $evidenceKeys);
        $backupEvidenced = is_array($backup) && ! $this->missingEvidence($backup['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $planEvidenced || ! $backupEvidenced) {
            $evidenceGaps[] = $this->issue('missing_change_recovery_evidence', "{$title} recovery plan or backup confirmation is not linked to known evidence.");
        }

        return $valid && $planEvidenced && $backupEvidenced;
    }

    /**
     * @param  array<string, array<string, mixed>>  $activePolicyExceptions
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validatePolicyExceptions(string $title, mixed $exceptionKeys, array $activePolicyExceptions, array &$decisionGaps): bool
    {
        if (! is_array($exceptionKeys)) {
            $decisionGaps[] = $this->issue('invalid_change_exception_references', "{$title} must explicitly list Policy Exception references, even when none apply.");

            return false;
        }

        foreach ($exceptionKeys as $exceptionKey) {
            if (! is_string($exceptionKey) || ! isset($activePolicyExceptions[$exceptionKey])) {
                $decisionGaps[] = $this->issue('invalid_change_policy_exception', "{$title} references a missing, inactive, or expired Policy Exception.");

                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateApprovals(
        string $title,
        mixed $approvals,
        ?ChangeType $type,
        mixed $risk,
        ?ChangeLifecycleStatus $status,
        ?Carbon $reviewedAt,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $required = in_array($status, [
            ChangeLifecycleStatus::Approved,
            ChangeLifecycleStatus::Scheduled,
            ChangeLifecycleStatus::Executing,
            ChangeLifecycleStatus::Verifying,
            ChangeLifecycleStatus::Closed,
            ChangeLifecycleStatus::Failed,
            ChangeLifecycleStatus::RolledBack,
        ], true);

        if (! is_array($approvals)) {
            if ($required || $status === ChangeLifecycleStatus::Rejected) {
                $decisionGaps[] = $this->issue('missing_change_approvals', "{$title} has no explicit Change Approval records.");
            }

            return [false, null];
        }

        $requiredTypes = match ($type) {
            ChangeType::Standard => ['standard_change_authority'],
            ChangeType::Normal => ['client_change_authority', 'firm_change_authority'],
            ChangeType::Emergency => ['emergency_change_authority'],
            default => [],
        };

        if (is_array($risk) && in_array($risk['classification'] ?? null, ['high', 'critical'], true)) {
            $requiredTypes[] = 'independent_risk_authority';
        }

        $approvedTypes = [];
        $latestApprovalAt = null;
        $hasRejection = false;

        foreach ($approvals as $approval) {
            if (! is_array($approval)) {
                continue;
            }

            $approvalType = (string) ($approval['approval_type'] ?? '');
            $outcome = ChangeApprovalOutcome::tryFrom((string) ($approval['outcome'] ?? ''));
            $decidedAt = $this->date($approval['decided_at'] ?? null);
            $complete = $approvalType !== ''
                && $outcome !== null
                && ! empty($approval['approver'])
                && ! empty($approval['authority_basis'])
                && $decidedAt !== null;

            if (! $complete) {
                $decisionGaps[] = $this->issue('incomplete_change_approval', "{$title} has an incomplete approval decision or authority record.");
            }

            if ($decidedAt !== null && $reviewedAt !== null && $decidedAt->lessThan($reviewedAt)) {
                $conflicts[] = $this->issue('change_approved_before_technical_review', "{$title} was approved before technical review completed.");
            }

            if ($outcome === ChangeApprovalOutcome::Approved && $complete) {
                $approvedTypes[] = $approvalType;
            }

            if ($outcome === ChangeApprovalOutcome::Rejected) {
                $hasRejection = true;

                if (empty($approval['reason'])) {
                    $decisionGaps[] = $this->issue('missing_change_rejection_reason', "{$title} has a rejection without a reason.");
                }
            }

            if ($this->missingEvidence($approval['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_change_approval_evidence', "{$title} approval {$approvalType} is not linked to known evidence.");
            }

            if ($decidedAt !== null && ($latestApprovalAt === null || $decidedAt->greaterThan($latestApprovalAt))) {
                $latestApprovalAt = $decidedAt;
            }
        }

        $duplicates = count($approvedTypes) !== count(array_unique($approvedTypes));
        $completeTypes = array_diff($requiredTypes, $approvedTypes) === [];

        if ($required && (! $completeTypes || $duplicates || $hasRejection)) {
            $decisionGaps[] = $this->issue('required_change_approvals_not_satisfied', "{$title} lacks exactly one approval from each required authority or contains a rejection.");
        }

        if ($status === ChangeLifecycleStatus::Rejected && ! $hasRejection) {
            $decisionGaps[] = $this->issue('rejected_change_without_decision', "{$title} is Rejected without an explicit rejection decision.");
        }

        return [$required && $completeTypes && ! $duplicates && ! $hasRejection, $latestApprovalAt];
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{bool, Carbon|null, Carbon|null}
     */
    private function validateSchedule(string $title, mixed $schedule, ?ChangeLifecycleStatus $status, ?Carbon $latestApprovalAt, Carbon $asOf, array &$conflicts, array &$decisionGaps): array
    {
        $required = in_array($status, [
            ChangeLifecycleStatus::Scheduled,
            ChangeLifecycleStatus::Executing,
            ChangeLifecycleStatus::Verifying,
            ChangeLifecycleStatus::Closed,
            ChangeLifecycleStatus::Failed,
            ChangeLifecycleStatus::RolledBack,
        ], true);

        if (! is_array($schedule)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_change_schedule', "{$title} has no bounded execution window.");
            }

            return [false, null, null];
        }

        $startsAt = $this->date($schedule['starts_at'] ?? null);
        $endsAt = $this->date($schedule['ends_at'] ?? null);
        $complete = $startsAt !== null
            && $endsAt !== null
            && $endsAt->greaterThan($startsAt)
            && ! empty($schedule['timezone'])
            && ! empty($schedule['communication_plan'])
            && ! empty($schedule['monitoring_plan'])
            && ! empty($schedule['abort_conditions']);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_change_schedule', "{$title} has an incomplete window, communication, monitoring, or abort definition.");
        }

        if ($startsAt !== null && ($latestApprovalAt === null || $startsAt->lessThan($latestApprovalAt))) {
            $conflicts[] = $this->issue('change_scheduled_before_approval', "{$title} execution window begins before every required approval.");
            $complete = false;
        }

        if ($status === ChangeLifecycleStatus::Scheduled && $endsAt !== null && $endsAt->lessThanOrEqualTo($asOf)) {
            $conflicts[] = $this->issue('scheduled_change_window_expired', "{$title} remains Scheduled after its approved execution window.");
        }

        return [$required && $complete, $startsAt, $endsAt];
    }

    /**
     * @param  array<string, array<string, mixed>>  $activeAccessGrants
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{array<string, mixed>|null, bool}
     */
    private function validateExecutorAccess(
        string $title,
        mixed $executor,
        mixed $accessGrantKey,
        mixed $engagementKey,
        mixed $scope,
        ?ChangeLifecycleStatus $status,
        array $activeAccessGrants,
        array &$decisionGaps,
    ): array {
        $required = in_array($status, [
            ChangeLifecycleStatus::Scheduled,
            ChangeLifecycleStatus::Executing,
            ChangeLifecycleStatus::Verifying,
            ChangeLifecycleStatus::Closed,
            ChangeLifecycleStatus::Failed,
            ChangeLifecycleStatus::RolledBack,
        ], true);
        $grant = is_string($accessGrantKey) ? ($activeAccessGrants[$accessGrantKey] ?? null) : null;
        $actor = is_array($grant) ? ($grant['actor'] ?? null) : null;
        $grantScope = is_array($grant) ? ($grant['scope'] ?? null) : null;
        $valid = is_array($executor)
            && ! empty($executor['key'])
            && ! empty($executor['name'])
            && is_array($grant)
            && is_array($actor)
            && ($actor['key'] ?? null) === $executor['key']
            && ($grant['engagement_key'] ?? null) === $engagementKey
            && is_array($scope)
            && is_array($grantScope)
            && ($grantScope['client_key'] ?? null) === ($scope['client_key'] ?? null)
            && ($grantScope['system'] ?? null) === ($scope['system'] ?? null)
            && ($grantScope['environment'] ?? null) === ($scope['environment'] ?? null);

        if ($required && ! $valid) {
            $decisionGaps[] = $this->issue('change_executor_without_active_access', "{$title} executor lacks a matching current Active Access Grant.");
        }

        return [$grant, $required && $valid];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null, Carbon|null}
     */
    private function validateExecution(
        string $title,
        mixed $execution,
        ?ChangeLifecycleStatus $status,
        ?Carbon $latestApprovalAt,
        ?Carbon $windowStartsAt,
        ?Carbon $windowEndsAt,
        mixed $executor,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $required = in_array($status, [
            ChangeLifecycleStatus::Executing,
            ChangeLifecycleStatus::Verifying,
            ChangeLifecycleStatus::Closed,
            ChangeLifecycleStatus::Failed,
            ChangeLifecycleStatus::RolledBack,
        ], true);

        if (! is_array($execution)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_change_execution', "{$title} has no Execution Record.");
            }

            return [false, null, null];
        }

        $startedAt = $this->date($execution['started_at'] ?? null);
        $completedAt = $this->date($execution['completed_at'] ?? null);
        $executorKey = is_array($executor) ? ($executor['key'] ?? null) : null;
        $complete = ($execution['executed_by'] ?? null) === $executorKey
            && ! empty($execution['authority_basis'])
            && ! empty($execution['artifact_identifier'])
            && ! empty($execution['target'])
            && ! empty($execution['deployment_output_reference'])
            && in_array($execution['result'] ?? null, ['in_progress', 'succeeded', 'failed', 'partial'], true)
            && $startedAt !== null;

        if ($status !== ChangeLifecycleStatus::Executing) {
            $complete = $complete && $completedAt !== null && $completedAt->greaterThanOrEqualTo($startedAt);
        }

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_change_execution', "{$title} has an incomplete or inconsistent Execution Record.");
        }

        if ($startedAt !== null && ($latestApprovalAt === null || $startedAt->lessThan($latestApprovalAt))) {
            $conflicts[] = $this->issue('change_executed_before_approval', "{$title} execution began before every required approval.");
            $complete = false;
        }

        if ($startedAt !== null && ($windowStartsAt === null || $windowEndsAt === null || $startedAt->lessThan($windowStartsAt) || $startedAt->greaterThanOrEqualTo($windowEndsAt))) {
            $conflicts[] = $this->issue('change_executed_outside_window', "{$title} execution began outside its approved window.");
            $complete = false;
        }

        $evidenced = ! $this->missingEvidence($execution['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_change_execution_evidence', "{$title} execution is not linked to known deployment evidence.");
        }

        return [$required && $complete && $evidenced, $startedAt, $completedAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateVerification(
        string $title,
        mixed $verification,
        ?ChangeLifecycleStatus $status,
        mixed $risk,
        ?Carbon $executionCompletedAt,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $required = in_array($status, [ChangeLifecycleStatus::Verifying, ChangeLifecycleStatus::Closed], true);

        if (! is_array($verification)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_change_verification', "{$title} has no Verification Record.");
            }

            return false;
        }

        $verifiedAt = $this->date($verification['verified_at'] ?? null);
        $highRisk = is_array($risk) && in_array($risk['classification'] ?? null, ['high', 'critical'], true);
        $complete = ! empty($verification['verified_by'])
            && ! empty($verification['expected_outcomes'])
            && ! empty($verification['observed_outcomes'])
            && in_array($verification['result'] ?? null, ['verified', 'failed', 'recovered'], true)
            && $verifiedAt !== null
            && (! $highRisk || ($verification['independent_from_executor'] ?? false) === true);

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_change_verification', "{$title} has an incomplete verification or lacks required independence.");
        }

        if ($verifiedAt !== null && ($executionCompletedAt === null || $verifiedAt->lessThan($executionCompletedAt))) {
            $conflicts[] = $this->issue('change_verified_before_execution_completed', "{$title} was verified before execution completed.");
            $complete = false;
        }

        $evidenced = ! $this->missingEvidence($verification['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_change_verification_evidence', "{$title} verification is not linked to known evidence.");
        }

        return $required && $complete && $evidenced;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateOutcome(
        string $title,
        ?ChangeLifecycleStatus $status,
        ?ChangeType $type,
        mixed $outcome,
        mixed $communication,
        mixed $postImplementationReview,
        mixed $closure,
        ?Carbon $executionStartedAt,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $terminal = in_array($status, [ChangeLifecycleStatus::Closed, ChangeLifecycleStatus::Failed, ChangeLifecycleStatus::RolledBack], true);

        if (! $terminal) {
            return true;
        }

        $outcomeComplete = is_array($outcome)
            && in_array($outcome['result'] ?? null, ['succeeded', 'failed', 'rolled_back'], true)
            && ! empty($outcome['summary'])
            && ! empty($outcome['follow_up_actions']);
        $communicationComplete = is_array($communication)
            && ! empty($communication['owner'])
            && ! empty($communication['audiences'])
            && ! empty($communication['status'])
            && ! empty($communication['communicated_at']);

        if (! $outcomeComplete || ! $communicationComplete) {
            $decisionGaps[] = $this->issue('incomplete_change_outcome', "{$title} has incomplete outcome, follow-up, or communication records.");
        }

        $valid = $outcomeComplete && $communicationComplete;

        foreach ([$outcome, $communication] as $record) {
            if (! is_array($record) || $this->missingEvidence($record['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_change_outcome_evidence', "{$title} outcome or communication is not linked to known evidence.");
                break;
            }
        }

        if ($status === ChangeLifecycleStatus::Closed) {
            $closureAt = is_array($closure) ? $this->date($closure['closed_at'] ?? null) : null;
            $closureComplete = is_array($closure)
                && ($outcome['result'] ?? null) === 'succeeded'
                && ! empty($closure['closed_by'])
                && ! empty($closure['authority_basis'])
                && ($closure['verification_complete'] ?? false) === true
                && ($closure['evidence_complete'] ?? false) === true
                && $closureAt !== null;

            if (! $closureComplete) {
                $decisionGaps[] = $this->issue('incomplete_change_closure', "{$title} is Closed without authorized, verified, evidenced closure.");
                $valid = false;
            }

            if (! is_array($closure) || $this->missingEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_change_closure_evidence', "{$title} closure is not linked to known evidence.");
                $valid = false;
            }
        }

        if ($status === ChangeLifecycleStatus::Failed && ($outcome['result'] ?? null) !== 'failed') {
            $conflicts[] = $this->issue('failed_change_outcome_mismatch', "{$title} lifecycle and outcome disagree.");
        }

        if ($status === ChangeLifecycleStatus::RolledBack && ($outcome['result'] ?? null) !== 'rolled_back') {
            $conflicts[] = $this->issue('rolled_back_change_outcome_mismatch', "{$title} lifecycle and outcome disagree.");
        }

        $reviewRequired = $type === ChangeType::Emergency
            || $status !== ChangeLifecycleStatus::Closed
            || (is_array($outcome) && ($outcome['client_impact'] ?? false) === true);

        if ($reviewRequired) {
            $reviewedAt = is_array($postImplementationReview) ? $this->date($postImplementationReview['reviewed_at'] ?? null) : null;
            $reviewComplete = is_array($postImplementationReview)
                && ! empty($postImplementationReview['owner'])
                && ! empty($postImplementationReview['findings'])
                && is_array($postImplementationReview['corrective_actions'] ?? null)
                && $reviewedAt !== null;

            if (! $reviewComplete) {
                $decisionGaps[] = $this->issue('missing_required_post_implementation_review', "{$title} requires a complete post-implementation review.");
                $valid = false;
            }

            if (! is_array($postImplementationReview) || $this->missingEvidence($postImplementationReview['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_change_review_evidence', "{$title} post-implementation review is not linked to known evidence.");
                $valid = false;
            }

            if ($reviewedAt !== null && $executionStartedAt !== null && $reviewedAt->lessThan($executionStartedAt)) {
                $conflicts[] = $this->issue('change_review_before_execution', "{$title} post-implementation review predates execution.");
            }
        }

        return $valid;
    }

    /** @param list<string> $evidenceKeys */
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

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }
}
