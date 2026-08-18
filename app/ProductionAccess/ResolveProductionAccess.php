<?php

namespace App\ProductionAccess;

use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveProductionAccess
{
    public function handle(
        ProductionAccessDefinition $definition,
        ResolvedEngagements $engagements,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedProductionAccess {
        $conflicts = [];
        $decisionGaps = [];
        $evidenceGaps = [];
        $readinessGaps = [];
        $lifecycleCounts = array_fill_keys(array_map(
            static fn (AccessGrantLifecycleStatus $status): string => $status->value,
            AccessGrantLifecycleStatus::cases(),
        ), 0);
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);

        $this->validateGrantStandard($definition->grantRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidenceRecords($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $policiesOperative] = $this->resolveGoverningPolicies(
            $definition,
            $policyRegistry,
            $effectiveAt,
            $conflicts,
            $readinessGaps,
        );
        $openEngagements = $this->openEngagements($engagements);
        $resolvedGrants = [];
        $grantKeys = [];

        foreach ($definition->accessGrants as $grant) {
            $resolvedGrants[] = $this->resolveGrant(
                grant: $grant,
                policiesOperative: $policiesOperative,
                openEngagements: $openEngagements,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                grantKeys: $grantKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedProductionAccess(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            grantRequirements: $definition->grantRequirements,
            accessGrants: $resolvedGrants,
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
    private function validateGrantStandard(array $requirements, array &$conflicts): void
    {
        $keys = [];

        foreach ($requirements as $requirement) {
            if (
                $requirement['key'] === ''
                || in_array($requirement['key'], $keys, true)
                || $requirement['label'] === ''
                || $requirement['question'] === ''
            ) {
                $conflicts[] = $this->issue('invalid_access_grant_requirement', 'A Production Access requirement is missing, incomplete, or duplicated.');
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
                $conflicts[] = $this->issue('invalid_access_evidence_key', 'A Production Access Evidence Record has a missing or duplicate key.');
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
                $evidenceGaps[] = $this->issue('incomplete_access_evidence_record', "Evidence Record {$key} is incomplete.");
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
        ProductionAccessDefinition $definition,
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
                $conflicts[] = $this->issue('missing_access_governing_policy', "Production Access references missing policy {$reference['key']} version {$reference['version']}.");
                $operative = false;
                $status = null;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $approvalEvidenceKey = is_array($approval) ? ($approval['evidence_record_key'] ?? null) : null;
                $policyEffectiveAt = $this->date($version['effective_at'] ?? null);
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
                    && in_array($approvalEvidenceKey, $policyEvidenceKeys, true));
            }

            if ($reference['required_for_activation'] && ! $operative) {
                $allRequiredOperative = false;
                $policyTitle = is_array($policy) ? (string) $policy['title'] : $reference['key'];
                $readinessGaps[] = $this->issue(
                    'access_governing_policy_not_effective',
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
     * @return array<string, array<string, mixed>>
     */
    private function openEngagements(ResolvedEngagements $engagements): array
    {
        $openEngagements = [];

        foreach ($engagements->engagements as $engagement) {
            if (
                ($engagement['operational_status'] ?? null) === 'open_engagement'
                && ($engagement['may_perform_client_work'] ?? false) === true
            ) {
                $openEngagements[(string) $engagement['key']] = $engagement;
            }
        }

        return $openEngagements;
    }

    /**
     * @param  array<string, mixed>  $grant
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $grantKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveGrant(
        array $grant,
        bool $policiesOperative,
        array $openEngagements,
        array $evidenceKeys,
        Carbon $asOf,
        array &$grantKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $conflictCount = count($conflicts);
        $decisionGapCount = count($decisionGaps);
        $evidenceGapCount = count($evidenceGaps);
        $key = (string) ($grant['key'] ?? '');
        $title = (string) ($grant['title'] ?? $key);
        $status = AccessGrantLifecycleStatus::tryFrom((string) ($grant['lifecycle_status'] ?? ''));
        $grantType = AccessGrantType::tryFrom((string) ($grant['grant_type'] ?? ''));

        if ($key === '' || in_array($key, $grantKeys, true)) {
            $conflicts[] = $this->issue('invalid_access_grant_key', 'An Access Grant has a missing or duplicate key.');
        }

        $grantKeys[] = $key;

        if ($status === null) {
            $conflicts[] = $this->issue('invalid_access_grant_status', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }

        if ($grantType === null) {
            $conflicts[] = $this->issue('invalid_access_grant_type', "{$title} has an invalid grant type.");
        }

        if ($this->containsSecretMaterial($grant)) {
            $conflicts[] = $this->issue('credential_secret_in_repository', "{$title} appears to contain credential secret material, which is prohibited.");
        }

        $requestEvidenced = ! $this->missingEvidence($grant['request_evidence_record_key'] ?? null, $evidenceKeys);

        if (! $requestEvidenced) {
            $evidenceGaps[] = $this->issue('missing_access_request_evidence', "{$title} request is not linked to a known Evidence Record.");
        }

        $identityValid = $this->validateIdentity($title, $grant['actor'] ?? null, $decisionGaps);
        [$engagement, $mandateValid] = $this->validateEngagementAndMandate(
            $title,
            $grant['engagement_key'] ?? null,
            $grant['scope'] ?? null,
            $openEngagements,
            $decisionGaps,
        );
        $scopeValid = $this->validateScope($title, $grant['scope'] ?? null, $decisionGaps);
        $riskValid = $this->validateRiskAndPrivilege(
            $title,
            $grant['risk'] ?? null,
            $grantType,
            $decisionGaps,
        );
        $prerequisitesSatisfied = $this->validatePrerequisites($title, $grant['prerequisites'] ?? null, $decisionGaps);
        $credentialHandlingValid = $this->validateCredentialHandling($title, $grant['credential_handling'] ?? null, $decisionGaps);
        $loggingDefined = $this->validateLogging($title, $grant['logging'] ?? null, $decisionGaps);
        [$validityCurrent, $startsAt, $expiresAt] = $this->validateValidity(
            $title,
            $grant['validity'] ?? null,
            $grant['lifecycle_control'] ?? null,
            $status,
            $asOf,
            $conflicts,
            $decisionGaps,
        );
        [$approvalsValid, $latestApprovalAt] = $this->validateApprovals(
            $title,
            $grant['approvals'] ?? null,
            $grantType,
            $status,
            $evidenceKeys,
            $decisionGaps,
            $evidenceGaps,
        );
        [$provisioningValid, $provisionedAt] = $this->validateProvisioning(
            $title,
            $grant['provisioning'] ?? null,
            $status,
            $latestApprovalAt,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $verificationValid = $this->validateVerification(
            $title,
            $grant['verification'] ?? null,
            $status,
            $provisionedAt,
            is_array($grant['scope'] ?? null) ? $grant['scope']['permission_set'] ?? [] : [],
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $terminalStateValid = $this->validateTerminalState(
            $title,
            $status,
            $grant['suspension'] ?? null,
            $grant['revocation'] ?? null,
            $grant['closure'] ?? null,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );

        $hasNoRecordIssues = count($conflicts) === $conflictCount
            && count($decisionGaps) === $decisionGapCount
            && count($evidenceGaps) === $evidenceGapCount;
        $mayUseAccess = $status === AccessGrantLifecycleStatus::Active
            && $policiesOperative
            && $identityValid
            && $engagement !== null
            && $mandateValid
            && $scopeValid
            && $riskValid
            && $prerequisitesSatisfied
            && $credentialHandlingValid
            && $loggingDefined
            && $validityCurrent
            && $approvalsValid
            && $provisioningValid
            && $verificationValid
            && $terminalStateValid
            && $requestEvidenced
            && $hasNoRecordIssues;

        if ($status === AccessGrantLifecycleStatus::Active && ! $mayUseAccess) {
            $conflicts[] = $this->issue('active_access_without_complete_gate', "{$title} is marked Active without satisfying every Production Access gate.");
        }

        return [
            ...$grant,
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'grant_type_label' => $grantType?->label() ?? 'Invalid',
            'engagement_title' => is_array($engagement) ? $engagement['title'] ?? null : null,
            'client_name' => is_array($engagement) ? $engagement['client_name'] ?? null : null,
            'may_use_access' => $mayUseAccess,
            'temporal_state' => match (true) {
                $startsAt !== null && $startsAt->greaterThan($asOf) => 'not_started',
                $expiresAt !== null && $expiresAt->lessThanOrEqualTo($asOf) => 'past_expiry',
                default => 'within_validity',
            },
            'operational_status' => match (true) {
                $mayUseAccess => 'active_authority',
                $status === AccessGrantLifecycleStatus::Active => 'blocked_active_grant',
                $status === AccessGrantLifecycleStatus::Approved => 'approved_not_provisioned',
                $status === AccessGrantLifecycleStatus::Provisioned => 'provisioned_not_active',
                $status === AccessGrantLifecycleStatus::Suspended => 'suspended',
                $status === AccessGrantLifecycleStatus::Expired => 'expired',
                $status === AccessGrantLifecycleStatus::Revoked => 'revoked',
                $status === AccessGrantLifecycleStatus::Closed => 'closed',
                default => 'pending',
            },
        ];
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateIdentity(string $title, mixed $actor, array &$decisionGaps): bool
    {
        $valid = is_array($actor)
            && ! empty($actor['key'])
            && ! empty($actor['name'])
            && ($actor['actor_type'] ?? null) === 'person'
            && ($actor['account_type'] ?? null) === 'named'
            && ! empty($actor['firm_relationship']);

        if (! $valid) {
            $decisionGaps[] = $this->issue('access_identity_not_named', "{$title} does not identify a named person and individually attributable account.");
        }

        return $valid;
    }

    /**
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{array<string, mixed>|null, bool}
     */
    private function validateEngagementAndMandate(
        string $title,
        mixed $engagementKey,
        mixed $scope,
        array $openEngagements,
        array &$decisionGaps,
    ): array {
        $engagement = is_string($engagementKey) ? ($openEngagements[$engagementKey] ?? null) : null;

        if (! is_array($engagement)) {
            $decisionGaps[] = $this->issue('access_without_open_engagement', "{$title} does not reference a current Open Engagement.");

            return [null, false];
        }

        $mandate = $engagement['client_mandate'] ?? null;
        $mandateValid = is_array($scope)
            && is_array($mandate)
            && ($scope['client_key'] ?? null) === ($engagement['client_key'] ?? null)
            && in_array($scope['system'] ?? null, $mandate['systems'] ?? [], true)
            && in_array($scope['environment'] ?? null, $mandate['environments'] ?? [], true)
            && in_array($scope['client_mandate_action'] ?? null, $mandate['permitted_actions'] ?? [], true);

        if (! $mandateValid) {
            $decisionGaps[] = $this->issue('access_outside_client_mandate', "{$title} is outside the Engagement Client Mandate.");
        }

        return [$engagement, $mandateValid];
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateScope(string $title, mixed $scope, array &$decisionGaps): bool
    {
        $valid = is_array($scope)
            && ! empty($scope['client_key'])
            && ! empty($scope['system'])
            && ! empty($scope['environment'])
            && ! empty($scope['account_identifier'])
            && ! empty($scope['permission_set'])
            && ! empty($scope['purpose'])
            && ! empty($scope['least_privilege_justification'])
            && ! empty($scope['client_mandate_action'])
            && ! empty($scope['engagement_access_basis'])
            && is_array($scope['prohibited_actions'] ?? null);

        if (! $valid) {
            $decisionGaps[] = $this->issue('incomplete_access_scope', "{$title} has an incomplete system, environment, permission, purpose, or least-privilege scope.");
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateRiskAndPrivilege(
        string $title,
        mixed $risk,
        ?AccessGrantType $grantType,
        array &$decisionGaps,
    ): bool {
        $valid = is_array($risk)
            && ! empty($risk['classification'])
            && ! empty($risk['risk_owner'])
            && is_array($risk['high_risk_actions'] ?? null)
            && ($risk['privileged'] ?? null) === ($grantType === AccessGrantType::Privileged);

        if ($grantType === AccessGrantType::Privileged) {
            $valid = $valid
                && ($risk['high_risk_actions_require_specific_approval'] ?? false) === true
                && ! empty($risk['high_risk_actions']);
        }

        if (! $valid) {
            $decisionGaps[] = $this->issue('incomplete_access_risk', "{$title} has an incomplete or inconsistent access-risk and privilege classification.");
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validatePrerequisites(string $title, mixed $prerequisites, array &$decisionGaps): bool
    {
        $valid = is_array($prerequisites)
            && ($prerequisites['identity_verified'] ?? false) === true
            && ($prerequisites['mfa_required'] ?? false) === true
            && ($prerequisites['mfa_verified'] ?? false) === true
            && ($prerequisites['device_compliant'] ?? false) === true
            && ($prerequisites['training_current'] ?? false) === true;

        if (! $valid) {
            $decisionGaps[] = $this->issue('unsatisfied_access_prerequisites', "{$title} has unsatisfied identity, MFA, device, or training prerequisites.");
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateCredentialHandling(string $title, mixed $handling, array &$decisionGaps): bool
    {
        $valid = is_array($handling)
            && ($handling['asset_owner'] ?? null) === 'client'
            && ! empty($handling['custodian'])
            && ! empty($handling['storage_reference'])
            && ! empty($handling['rotation_owner'])
            && ! empty($handling['rotation_trigger'])
            && ($handling['secret_material_present'] ?? null) === false;

        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_credential_custody', "{$title} has incomplete Client ownership, custody, storage, or rotation metadata.");
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateLogging(string $title, mixed $logging, array &$decisionGaps): bool
    {
        $valid = is_array($logging)
            && ($logging['activity_logging_required'] ?? false) === true
            && ! empty($logging['log_owner'])
            && ! empty($logging['retention_basis'])
            && ! empty($logging['evidence_requirements']);

        if (! $valid) {
            $decisionGaps[] = $this->issue('incomplete_access_logging', "{$title} has incomplete activity logging, ownership, retention, or evidence requirements.");
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{bool, Carbon|null, Carbon|null}
     */
    private function validateValidity(
        string $title,
        mixed $validity,
        mixed $lifecycleControl,
        ?AccessGrantLifecycleStatus $status,
        Carbon $asOf,
        array &$conflicts,
        array &$decisionGaps,
    ): array {
        $startsAt = is_array($validity) ? $this->date($validity['starts_at'] ?? null) : null;
        $expiresAt = is_array($validity) ? $this->date($validity['expires_at'] ?? null) : null;
        $reviewAt = is_array($validity) ? $this->date($validity['review_at'] ?? null) : null;
        $complete = $startsAt !== null
            && $expiresAt !== null
            && $reviewAt !== null
            && $expiresAt->greaterThan($startsAt)
            && $reviewAt->greaterThan($startsAt)
            && $reviewAt->lessThanOrEqualTo($expiresAt)
            && is_array($lifecycleControl)
            && ! empty($lifecycleControl['revocation_owner'])
            && ! empty($lifecycleControl['revocation_method'])
            && ($lifecycleControl['expiry_enforced'] ?? false) === true;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_access_validity', "{$title} has an incomplete or inconsistent validity, review, or revocation definition.");
        }

        $current = $startsAt !== null
            && $expiresAt !== null
            && $startsAt->lessThanOrEqualTo($asOf)
            && $expiresAt->greaterThan($asOf);
        $reviewCurrent = $reviewAt !== null && $reviewAt->greaterThan($asOf);

        if ($status === AccessGrantLifecycleStatus::Active && ! $current) {
            $conflicts[] = $this->issue('active_access_outside_validity', "{$title} is Active outside its approved validity period.");
        }

        if ($status === AccessGrantLifecycleStatus::Active && ! $reviewCurrent) {
            $conflicts[] = $this->issue('active_access_review_overdue', "{$title} remains Active after its required review date.");
        }

        return [$complete && $current && $reviewCurrent, $startsAt, $expiresAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateApprovals(
        string $title,
        mixed $approvals,
        ?AccessGrantType $grantType,
        ?AccessGrantLifecycleStatus $status,
        array $evidenceKeys,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $requiresApproval = in_array($status, [
            AccessGrantLifecycleStatus::Approved,
            AccessGrantLifecycleStatus::Provisioned,
            AccessGrantLifecycleStatus::Active,
            AccessGrantLifecycleStatus::Suspended,
            AccessGrantLifecycleStatus::Expired,
            AccessGrantLifecycleStatus::Revoked,
            AccessGrantLifecycleStatus::Closed,
        ], true);

        if (! is_array($approvals)) {
            if ($requiresApproval) {
                $decisionGaps[] = $this->issue('missing_access_approvals', "{$title} has no explicit Access Approval records.");
            }

            return [false, null];
        }

        $requiredTypes = ['client_system_owner', 'firm_access_authority'];

        if ($grantType === AccessGrantType::Privileged) {
            $requiredTypes[] = 'independent_privileged_authority';
        }

        $approvedTypes = [];
        $latestApprovalAt = null;
        $hasRejectedApproval = false;

        foreach ($approvals as $approval) {
            if (! is_array($approval)) {
                continue;
            }

            $type = (string) ($approval['approval_type'] ?? '');
            $outcome = AccessApprovalOutcome::tryFrom((string) ($approval['outcome'] ?? ''));
            $decidedAt = $this->date($approval['decided_at'] ?? null);
            $complete = $type !== ''
                && $outcome !== null
                && ! empty($approval['approver'])
                && ! empty($approval['authority_basis'])
                && $decidedAt !== null;

            if (! $complete) {
                $decisionGaps[] = $this->issue('incomplete_access_approval', "{$title} has an incomplete approval decision or authority record.");
            }

            if ($outcome === AccessApprovalOutcome::Approved && $complete) {
                $approvedTypes[] = $type;
            }

            if ($outcome === AccessApprovalOutcome::Rejected && empty($approval['reason'])) {
                $decisionGaps[] = $this->issue('missing_access_rejection_reason', "{$title} has a rejection without a reason.");
            }

            if ($outcome === AccessApprovalOutcome::Rejected) {
                $hasRejectedApproval = true;
            }

            if ($this->missingEvidence($approval['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_access_approval_evidence', "{$title} approval {$type} is not linked to a known Evidence Record.");
            }

            if ($decidedAt !== null && ($latestApprovalAt === null || $decidedAt->greaterThan($latestApprovalAt))) {
                $latestApprovalAt = $decidedAt;
            }
        }

        $duplicates = count($approvedTypes) !== count(array_unique($approvedTypes));
        $completeTypes = array_diff($requiredTypes, $approvedTypes) === [];

        if ($requiresApproval && (! $completeTypes || $duplicates)) {
            $decisionGaps[] = $this->issue('required_access_approvals_not_satisfied', "{$title} lacks exactly one approval from each required authority.");
        }

        return [$requiresApproval && $completeTypes && ! $duplicates && ! $hasRejectedApproval, $latestApprovalAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateProvisioning(
        string $title,
        mixed $provisioning,
        ?AccessGrantLifecycleStatus $status,
        ?Carbon $latestApprovalAt,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $requiresProvisioning = in_array($status, [
            AccessGrantLifecycleStatus::Provisioned,
            AccessGrantLifecycleStatus::Active,
            AccessGrantLifecycleStatus::Suspended,
            AccessGrantLifecycleStatus::Expired,
            AccessGrantLifecycleStatus::Revoked,
            AccessGrantLifecycleStatus::Closed,
        ], true);

        if (! is_array($provisioning)) {
            if ($requiresProvisioning) {
                $decisionGaps[] = $this->issue('missing_access_provisioning', "{$title} has no Provisioning Record.");
            }

            return [false, null];
        }

        $provisionedAt = $this->date($provisioning['provisioned_at'] ?? null);
        $complete = ! empty($provisioning['provisioned_by'])
            && ! empty($provisioning['authority_basis'])
            && ! empty($provisioning['mechanism'])
            && ! empty($provisioning['account_identifier'])
            && $provisionedAt !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_access_provisioning', "{$title} has an incomplete Provisioning Record.");
        }

        if ($provisionedAt !== null && ($latestApprovalAt === null || $provisionedAt->lessThan($latestApprovalAt))) {
            $conflicts[] = $this->issue('access_provisioned_before_approval', "{$title} was provisioned before every required approval.");
            $complete = false;
        }

        $evidenced = ! $this->missingEvidence($provisioning['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_access_provisioning_evidence', "{$title} Provisioning Record is not linked to known evidence.");
        }

        return [$requiresProvisioning && $complete && $evidenced, $provisionedAt];
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
        ?AccessGrantLifecycleStatus $status,
        ?Carbon $provisionedAt,
        mixed $requestedPermissions,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $requiresVerification = in_array($status, [
            AccessGrantLifecycleStatus::Active,
            AccessGrantLifecycleStatus::Suspended,
            AccessGrantLifecycleStatus::Expired,
            AccessGrantLifecycleStatus::Revoked,
            AccessGrantLifecycleStatus::Closed,
        ], true);

        if (! is_array($verification)) {
            if ($requiresVerification) {
                $decisionGaps[] = $this->issue('missing_access_verification', "{$title} has no Access Verification Record.");
            }

            return false;
        }

        $verifiedAt = $this->date($verification['verified_at'] ?? null);
        $observedPermissions = is_array($verification['observed_permissions'] ?? null)
            ? $verification['observed_permissions']
            : [];
        $permissions = is_array($requestedPermissions) ? $requestedPermissions : [];
        sort($observedPermissions);
        sort($permissions);
        $complete = ! empty($verification['verified_by'])
            && ($verification['result'] ?? null) === 'verified'
            && $verifiedAt !== null
            && $observedPermissions !== [];

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_access_verification', "{$title} has an incomplete or unsuccessful Access Verification Record.");
        }

        if ($verifiedAt !== null && ($provisionedAt === null || $verifiedAt->lessThan($provisionedAt))) {
            $conflicts[] = $this->issue('access_verified_before_provisioning', "{$title} was verified before provisioning.");
            $complete = false;
        }

        if ($observedPermissions !== $permissions) {
            $conflicts[] = $this->issue('verified_permissions_mismatch', "{$title} observed permissions do not match its approved permission set.");
            $complete = false;
        }

        $evidenced = ! $this->missingEvidence($verification['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_access_verification_evidence', "{$title} verification is not linked to known evidence.");
        }

        return $requiresVerification && $complete && $evidenced;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateTerminalState(
        string $title,
        ?AccessGrantLifecycleStatus $status,
        mixed $suspension,
        mixed $revocation,
        mixed $closure,
        array $evidenceKeys,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): bool {
        $requirements = match ($status) {
            AccessGrantLifecycleStatus::Suspended => ['record' => $suspension, 'label' => 'Suspension'],
            AccessGrantLifecycleStatus::Revoked => ['record' => $revocation, 'label' => 'Revocation'],
            AccessGrantLifecycleStatus::Closed => ['record' => $closure, 'label' => 'Closure'],
            default => null,
        };

        if ($requirements === null) {
            if ($status === AccessGrantLifecycleStatus::Active && (is_array($suspension) || is_array($revocation) || is_array($closure))) {
                $conflicts[] = $this->issue('active_access_with_terminal_record', "{$title} is Active while carrying a suspension, revocation, or closure record.");

                return false;
            }

            return true;
        }

        $record = $requirements['record'];
        $label = $requirements['label'];

        if (! is_array($record)) {
            $decisionGaps[] = $this->issue('missing_access_terminal_record', "{$title} has no {$label} Record for its lifecycle state.");

            return false;
        }

        $complete = ! empty($record['actor'])
            && ! empty($record['authority_basis'])
            && ! empty($record['reason'])
            && $this->date($record['recorded_at'] ?? null) !== null;

        if (! $complete) {
            $decisionGaps[] = $this->issue('incomplete_access_terminal_record', "{$title} has an incomplete {$label} Record.");
        }

        $evidenced = ! $this->missingEvidence($record['evidence_record_key'] ?? null, $evidenceKeys);

        if (! $evidenced) {
            $evidenceGaps[] = $this->issue('missing_access_terminal_evidence', "{$title} {$label} Record is not linked to known evidence.");
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

    /**
     * @param  array<string, mixed>  $record
     */
    private function containsSecretMaterial(array $record): bool
    {
        $forbiddenKeys = ['password', 'secret', 'secret_value', 'token', 'private_key', 'credential_value', 'recovery_code'];

        foreach ($record as $key => $value) {
            if (in_array(mb_strtolower($key), $forbiddenKeys, true) && $value !== null && $value !== '') {
                return true;
            }

            if (is_array($value) && $this->containsSecretMaterial($value)) {
                return true;
            }
        }

        return false;
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
