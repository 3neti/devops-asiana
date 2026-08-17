<?php

namespace App\BreakGlassAccess;

use App\Engagements\ResolvedEngagements;
use App\Incidents\ResolvedIncidents;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveBreakGlassAccess
{
    public function handle(
        BreakGlassAccessDefinition $definition,
        ResolvedEngagements $engagements,
        ResolvedIncidents $incidents,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedBreakGlassAccess {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $decisionGaps */
        $decisionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $readinessGaps */
        $readinessGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $lifecycleCounts = array_fill_keys(array_map(
            static fn (BreakGlassLifecycleStatus $status): string => $status->value,
            BreakGlassLifecycleStatus::cases(),
        ), 0);

        $this->validateRequirements($definition->recordRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $policiesOperative] = $this->resolvePolicies(
            $definition,
            $policyRegistry,
            $effectiveAt,
            $conflicts,
            $readinessGaps,
        );
        $openEngagements = $this->openEngagements($engagements);
        $declaredIncidents = $this->declaredIncidents($incidents);
        /** @var list<string> $recordKeys */
        $recordKeys = [];
        /** @var list<array<string, mixed>> $resolvedRecords */
        $resolvedRecords = [];

        foreach ($definition->accessRecords as $record) {
            $resolvedRecords[] = $this->resolveRecord(
                record: $record,
                policiesOperative: $policiesOperative,
                openEngagements: $openEngagements,
                declaredIncidents: $declaredIncidents,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                recordKeys: $recordKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedBreakGlassAccess(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            recordRequirements: $definition->recordRequirements,
            accessRecords: $resolvedRecords,
            evidenceRecords: $definition->evidenceRecords,
            lifecycleCounts: $lifecycleCounts,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
            readinessGaps: $readinessGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  array<string, array<string, mixed>>  $declaredIncidents
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $recordKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveRecord(
        array $record,
        bool $policiesOperative,
        array $openEngagements,
        array $declaredIncidents,
        array $evidenceKeys,
        Carbon $asOf,
        array &$recordKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $key = (string) ($record['key'] ?? '');
        $title = (string) ($record['title'] ?? $key);
        $status = BreakGlassLifecycleStatus::tryFrom((string) ($record['lifecycle_status'] ?? ''));
        $actorKey = is_array($record['actor'] ?? null) ? (string) ($record['actor']['key'] ?? '') : '';

        if ($key === '' || in_array($key, $recordKeys, true)) {
            $conflicts[] = $this->issue('invalid_break_glass_key', 'A Break-glass Access Record has a missing or duplicate key.');
        }
        $recordKeys[] = $key;

        if ($status === null) {
            $conflicts[] = $this->issue('invalid_break_glass_lifecycle', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }

        if ($this->containsSecretMaterial($record)) {
            $conflicts[] = $this->issue('break_glass_secret_in_repository', "{$title} appears to contain credential secret material, which is prohibited.");
        }
        $notExtendedInPlace = ! array_key_exists('renewed_from', $record) && ! array_key_exists('extended_until', $record);
        if (! $notExtendedInPlace) {
            $conflicts[] = $this->issue('silent_break_glass_extension', "{$title} attempts to renew or extend emergency authority in place; a new independently approved record is required.");
        }

        $engagementKey = is_string($record['engagement_key'] ?? null) ? $record['engagement_key'] : '';
        $incidentKey = is_string($record['incident_key'] ?? null) ? $record['incident_key'] : '';
        $engagement = $openEngagements[$engagementKey] ?? null;
        $incident = $declaredIncidents[$incidentKey] ?? null;
        $requiresActivationControls = $status !== null && in_array($status, [
            BreakGlassLifecycleStatus::Activated,
            BreakGlassLifecycleStatus::Expired,
            BreakGlassLifecycleStatus::Revoked,
            BreakGlassLifecycleStatus::UnderReview,
            BreakGlassLifecycleStatus::Closed,
        ], true);

        if ($engagement === null) {
            $conflicts[] = $this->issue('break_glass_without_open_engagement', "{$title} does not reference an Open Engagement.");
        }
        if ($requiresActivationControls && $incident === null) {
            $conflicts[] = $this->issue('break_glass_without_active_incident', "{$title} has reached activation without a declared active Incident.");
        }
        if ($incident !== null && ($incident['engagement_key'] ?? null) !== $engagementKey) {
            $conflicts[] = $this->issue('break_glass_incident_engagement_mismatch', "{$title} Incident and Engagement do not match.");
        }

        $emergencyValid = $this->validateEmergency($title, $record['emergency'] ?? null, $decisionGaps);
        $identityValid = $this->validateIdentity($title, $record['actor'] ?? null, $decisionGaps);
        $scopeValid = $this->validateScope($title, $record['scope'] ?? null, $engagement, $decisionGaps, $conflicts);
        $riskValid = $this->validateRisk($title, $record['risk'] ?? null, $decisionGaps);
        $identityControlsValid = $this->validateIdentityControls($title, $record['identity_controls'] ?? null, $decisionGaps);
        [$approvalsValid, $latestApprovalAt] = $this->validateApprovals(
            $title,
            $record['approvals'] ?? null,
            $actorKey,
            $evidenceKeys,
            $requiresActivationControls || $status === BreakGlassLifecycleStatus::Authorized,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        [$windowValid, $windowState, $activatesAt, $expiresAt] = $this->validateWindow(
            $title,
            $record['window'] ?? null,
            $latestApprovalAt,
            $asOf,
            $requiresActivationControls || $status === BreakGlassLifecycleStatus::Authorized,
            $conflicts,
            $decisionGaps,
        );
        $activationValid = $this->validateActivation(
            $title,
            $record['activation'] ?? null,
            $activatesAt,
            $latestApprovalAt,
            $evidenceKeys,
            $requiresActivationControls,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $activityValid = $this->validateActivityLog(
            $title,
            $record['activity_log'] ?? null,
            $activatesAt,
            $expiresAt,
            $actorKey,
            $evidenceKeys,
            $requiresActivationControls,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );
        $monitoringValid = $this->validateMonitoring($title, $record['monitoring'] ?? null, $actorKey, $evidenceKeys, $requiresActivationControls, $conflicts, $decisionGaps, $evidenceGaps);
        $terminationValid = $this->validateTermination($title, $record['termination'] ?? null, $expiresAt, $evidenceKeys, $status, $decisionGaps, $evidenceGaps);
        $disclosureValid = $this->validateDisclosure($title, $record['disclosure'] ?? null, $evidenceKeys, $status, $decisionGaps, $evidenceGaps);
        $reviewValid = $this->validateReview($title, $record['retrospective_review'] ?? null, $actorKey, $evidenceKeys, $status, $conflicts, $decisionGaps, $evidenceGaps);
        $actionsAccountable = $this->validateCorrectiveActions($title, $record['corrective_actions'] ?? null, $status, $evidenceKeys, $decisionGaps, $evidenceGaps);

        $activationGate = $policiesOperative
            && $notExtendedInPlace
            && $engagement !== null
            && $incident !== null
            && $emergencyValid
            && $identityValid
            && $scopeValid
            && $riskValid
            && $identityControlsValid
            && $approvalsValid
            && $windowValid
            && $activationValid
            && $activityValid
            && $monitoringValid;
        $mayUse = $status === BreakGlassLifecycleStatus::Activated
            && $activationGate
            && $windowState === 'active';

        if ($status === BreakGlassLifecycleStatus::Activated && $windowState === 'expired') {
            $conflicts[] = $this->issue('break_glass_active_after_expiry', "{$title} remains marked Activated after its absolute authority expiry.");
        }

        $closurePrerequisites = $activationGate
            && $terminationValid
            && $disclosureValid
            && $reviewValid
            && $actionsAccountable;
        $closure = $record['closure'] ?? null;
        if ($status === BreakGlassLifecycleStatus::Closed) {
            if (! $closurePrerequisites || ! $this->completeRecord($closure, ['closed_by', 'authority_basis', 'closed_at', 'evidence_record_key'])) {
                $decisionGaps[] = $this->issue('premature_break_glass_closure', "{$title} is Closed without verified removal, disclosure, independent review, corrective-action accountability, and closure authority.");
            }
            $this->requireEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys, "{$title} closure", $evidenceGaps);
        }

        return array_merge($record, [
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'engagement_title' => $engagement['title'] ?? null,
            'client_name' => $engagement['client_name'] ?? null,
            'incident_title' => $incident['title'] ?? null,
            'window_state' => $windowState,
            'may_use_break_glass' => $mayUse,
            'operational_status' => match (true) {
                $status === BreakGlassLifecycleStatus::Closed && $closurePrerequisites => 'closed_verified',
                $status === BreakGlassLifecycleStatus::Closed => 'blocked_closure',
                $status === BreakGlassLifecycleStatus::Activated && $windowState === 'expired' => 'expired_authority',
                $mayUse => 'active_emergency_authority',
                in_array($status, [BreakGlassLifecycleStatus::Expired, BreakGlassLifecycleStatus::Revoked, BreakGlassLifecycleStatus::UnderReview], true) && ! $reviewValid => 'awaiting_review',
                $status === BreakGlassLifecycleStatus::UnderReview && $closurePrerequisites => 'ready_for_closure',
                $status === BreakGlassLifecycleStatus::Rejected => 'rejected',
                $status === BreakGlassLifecycleStatus::Cancelled => 'cancelled',
                default => 'pending',
            },
        ]);
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateEmergency(string $title, mixed $emergency, array &$decisionGaps): bool
    {
        $valid = $this->completeRecord($emergency, ['condition', 'material_harm_if_delayed', 'why_ordinary_process_is_insufficient', 'requested_by', 'requested_at']);
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_emergency', "{$title} lacks a complete defined-emergency justification.");
        }

        return $valid;
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateIdentity(string $title, mixed $actor, array &$decisionGaps): bool
    {
        $valid = $this->completeRecord($actor, ['key', 'name', 'account_identifier'])
            && ($actor['account_type'] ?? null) === 'named';
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_identity', "{$title} requires one named person and attributable named account.");
        }

        return $valid;
    }

    /**
     * @param  array<string, mixed>|null  $engagement
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateScope(string $title, mixed $scope, ?array $engagement, array &$decisionGaps, array &$conflicts): bool
    {
        $valid = $this->completeRecord($scope, ['client_key', 'system', 'environment', 'permissions', 'purpose', 'client_mandate_action', 'permitted_actions', 'prohibited_actions'])
            && ($scope['permissions'] ?? []) !== []
            && ($scope['permitted_actions'] ?? []) !== []
            && ($scope['prohibited_actions'] ?? []) !== [];
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_scope', "{$title} lacks a minimum explicit emergency scope and prohibited-action boundary.");

            return false;
        }
        if ($engagement === null) {
            return false;
        }
        $mandate = $engagement['client_mandate'] ?? [];
        $insideMandate = ($engagement['client_key'] ?? null) === ($scope['client_key'] ?? null)
            && in_array($scope['system'], $mandate['systems'] ?? [], true)
            && in_array($scope['environment'], $mandate['environments'] ?? [], true)
            && in_array($scope['client_mandate_action'], $mandate['permitted_actions'] ?? [], true);
        if (! $insideMandate) {
            $conflicts[] = $this->issue('break_glass_outside_client_mandate', "{$title} scope is outside the Engagement Client Mandate.");
        }

        return $insideMandate;
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateRisk(string $title, mixed $risk, array &$decisionGaps): bool
    {
        $valid = $this->completeRecord($risk, ['classification', 'impact', 'data_sensitivity', 'risk_owner', 'controls', 'residual_risk']);
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_risk', "{$title} lacks complete emergency risk ownership and controls.");
        }

        return $valid;
    }

    /** @param list<array{code: string, message: string}> $decisionGaps */
    private function validateIdentityControls(string $title, mixed $controls, array &$decisionGaps): bool
    {
        $valid = $this->completeRecord($controls, ['mfa', 'credential_owner', 'custody', 'vault_reference', 'session_attribution', 'rotation_required', 'secret_material_present'])
            && ($controls['mfa'] ?? false) === true
            && ($controls['secret_material_present'] ?? true) === false;
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_identity_controls', "{$title} lacks safe identity, custody, MFA, attribution, or rotation controls.");
        }

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array{bool, Carbon|null}
     */
    private function validateApprovals(string $title, mixed $approvals, string $actorKey, array $evidenceKeys, bool $required, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! is_array($approvals)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_approvals', "{$title} lacks emergency approvals.");
            }

            return [false, null];
        }
        $requiredTypes = ['client_emergency_authority', 'firm_emergency_authority', 'independent_security_authority'];
        $approvedTypes = [];
        $latest = null;
        $valid = true;
        foreach ($approvals as $approval) {
            if (! is_array($approval)) {
                $valid = false;

                continue;
            }
            $type = (string) ($approval['approval_type'] ?? '');
            $decidedAt = $this->date($approval['decided_at'] ?? null);
            $complete = in_array($type, $requiredTypes, true)
                && ($approval['outcome'] ?? null) === 'approved'
                && ! empty($approval['approver_key'])
                && ! empty($approval['approver'])
                && ! empty($approval['authority_basis'])
                && $decidedAt !== null;
            if (! $complete) {
                $valid = false;
            }
            if (($approval['approver_key'] ?? null) === $actorKey) {
                $conflicts[] = $this->issue('self_approved_break_glass', "{$title} actor cannot independently approve their own emergency access.");
                $valid = false;
            }
            $approvedTypes[] = $type;
            if ($decidedAt !== null && ($latest === null || $decidedAt->greaterThan($latest))) {
                $latest = $decidedAt;
            }
            $this->requireEvidence($approval['evidence_record_key'] ?? null, $evidenceKeys, "{$title} {$type} approval", $evidenceGaps);
        }
        if (array_diff($requiredTypes, $approvedTypes) !== [] || count($approvedTypes) !== count(array_unique($approvedTypes))) {
            $decisionGaps[] = $this->issue('incomplete_break_glass_authority', "{$title} requires exactly one approval from each emergency authority.");
            $valid = false;
        }

        return [$required && $valid, $latest];
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @return array{bool, string, Carbon|null, Carbon|null}
     */
    private function validateWindow(string $title, mixed $window, ?Carbon $latestApprovalAt, Carbon $asOf, bool $required, array &$conflicts, array &$decisionGaps): array
    {
        if (! is_array($window)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_window', "{$title} lacks a fixed activation and expiry window.");
            }

            return [false, 'undefined', null, null];
        }
        $activatesAt = $this->date($window['activates_at'] ?? null);
        $expiresAt = $this->date($window['expires_at'] ?? null);
        $valid = $activatesAt !== null && $expiresAt !== null && $expiresAt->greaterThan($activatesAt)
            && ($window['automatic_expiry'] ?? false) === true
            && ($window['renewal_permitted'] ?? true) === false;
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_window', "{$title} lacks an ordered, automatic, non-renewable authority window.");
        }
        if ($activatesAt !== null && ($latestApprovalAt === null || $activatesAt->lessThan($latestApprovalAt))) {
            $conflicts[] = $this->issue('break_glass_activated_before_approval', "{$title} activation begins before every required approval.");
            $valid = false;
        }
        $state = match (true) {
            $activatesAt === null || $expiresAt === null => 'undefined',
            $asOf->lessThan($activatesAt) => 'before_window',
            $asOf->greaterThanOrEqualTo($expiresAt) => 'expired',
            default => 'active',
        };

        return [$required && $valid, $state, $activatesAt, $expiresAt];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateActivation(string $title, mixed $activation, ?Carbon $activatesAt, ?Carbon $latestApprovalAt, array $evidenceKeys, bool $required, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! is_array($activation)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_activation', "{$title} lacks an Activation Record.");
            }

            return false;
        }
        $activatedAt = $this->date($activation['activated_at'] ?? null);
        $valid = $this->completeRecord($activation, ['activated_by', 'authority_basis', 'account_identifier', 'verification', 'activated_at', 'evidence_record_key'])
            && ($activation['verification'] ?? null) === 'verified';
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_activation', "{$title} has an incomplete Activation Record.");
        }
        if ($activatedAt !== null && ($latestApprovalAt === null || $activatedAt->lessThan($latestApprovalAt) || ($activatesAt !== null && $activatedAt->lessThan($activatesAt)))) {
            $conflicts[] = $this->issue('break_glass_activation_order_conflict', "{$title} was activated before approval or its authorized window.");
            $valid = false;
        }
        $this->requireEvidence($activation['evidence_record_key'] ?? null, $evidenceKeys, "{$title} activation", $evidenceGaps);

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateActivityLog(string $title, mixed $activityLog, ?Carbon $activatesAt, ?Carbon $expiresAt, string $actorKey, array $evidenceKeys, bool $required, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! is_array($activityLog) || $activityLog === []) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_activity_log', "{$title} lacks a complete emergency activity log.");
            }

            return false;
        }
        $valid = true;
        $entryKeys = [];
        $previous = null;
        foreach ($activityLog as $entry) {
            $entryKey = is_array($entry) ? (string) ($entry['key'] ?? '') : '';
            $occurredAt = is_array($entry) ? $this->date($entry['occurred_at'] ?? null) : null;
            $complete = is_array($entry) && $this->completeRecord($entry, ['key', 'occurred_at', 'actor_key', 'action', 'target', 'result', 'source', 'evidence_record_key']);
            if (! $complete || $entryKey === '' || in_array($entryKey, $entryKeys, true) || ($entry['actor_key'] ?? null) !== $actorKey) {
                $conflicts[] = $this->issue('invalid_break_glass_activity_entry', "{$title} has an incomplete, duplicate, or unattributed activity entry.");
                $valid = false;
            }
            if ($occurredAt === null || ($previous !== null && $occurredAt->lessThan($previous)) || ($activatesAt !== null && $occurredAt->lessThan($activatesAt)) || ($expiresAt !== null && $occurredAt->greaterThan($expiresAt))) {
                $conflicts[] = $this->issue('break_glass_activity_outside_window', "{$title} activity is unordered or outside the authorized window.");
                $valid = false;
            }
            $previous = $occurredAt ?? $previous;
            $entryKeys[] = $entryKey;
            $this->requireEvidence(is_array($entry) ? ($entry['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} activity {$entryKey}", $evidenceGaps);
        }

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateMonitoring(string $title, mixed $monitoring, string $actorKey, array $evidenceKeys, bool $required, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! is_array($monitoring)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_monitoring', "{$title} lacks independent session monitoring.");
            }

            return false;
        }
        $valid = $this->completeRecord($monitoring, ['monitored_by_key', 'monitored_by', 'mechanism', 'scope_violation_response', 'result', 'evidence_record_key']);
        if (($monitoring['monitored_by_key'] ?? null) === $actorKey) {
            $conflicts[] = $this->issue('self_monitored_break_glass', "{$title} actor cannot independently monitor their own emergency session.");
            $valid = false;
        }
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_monitoring', "{$title} has incomplete independent session monitoring.");
        }
        $this->requireEvidence($monitoring['evidence_record_key'] ?? null, $evidenceKeys, "{$title} monitoring", $evidenceGaps);

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateTermination(string $title, mixed $termination, ?Carbon $expiresAt, array $evidenceKeys, ?BreakGlassLifecycleStatus $status, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $required = $status !== null && in_array($status, [BreakGlassLifecycleStatus::Expired, BreakGlassLifecycleStatus::Revoked, BreakGlassLifecycleStatus::UnderReview, BreakGlassLifecycleStatus::Closed], true);
        if (! is_array($termination)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_termination', "{$title} lacks verified technical access removal.");
            }

            return false;
        }
        $terminatedAt = $this->date($termination['terminated_at'] ?? null);
        $valid = $this->completeRecord($termination, ['reason', 'terminated_by', 'terminated_at', 'permissions_removed', 'verified_by', 'verification_result', 'credential_rotated', 'evidence_record_key'])
            && ($termination['permissions_removed'] ?? false) === true
            && ($termination['verification_result'] ?? null) === 'verified'
            && $terminatedAt !== null;
        if ($status === BreakGlassLifecycleStatus::Expired && $expiresAt !== null && $terminatedAt !== null && $terminatedAt->lessThan($expiresAt)) {
            $valid = false;
        }
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_termination', "{$title} does not prove complete technical removal and independent verification.");
        }
        $this->requireEvidence($termination['evidence_record_key'] ?? null, $evidenceKeys, "{$title} termination", $evidenceGaps);

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateDisclosure(string $title, mixed $disclosure, array $evidenceKeys, ?BreakGlassLifecycleStatus $status, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $required = $status !== null && in_array($status, [BreakGlassLifecycleStatus::Expired, BreakGlassLifecycleStatus::Revoked, BreakGlassLifecycleStatus::UnderReview, BreakGlassLifecycleStatus::Closed], true);
        if (! is_array($disclosure)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_disclosure', "{$title} lacks Client and Responsible Partner disclosure.");
            }

            return false;
        }
        $valid = $this->completeRecord($disclosure, ['communicated_by', 'client_audience', 'responsible_partner', 'summary', 'communicated_at', 'evidence_record_key']);
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_disclosure', "{$title} has incomplete emergency-use disclosure.");
        }
        $this->requireEvidence($disclosure['evidence_record_key'] ?? null, $evidenceKeys, "{$title} disclosure", $evidenceGaps);

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateReview(string $title, mixed $review, string $actorKey, array $evidenceKeys, ?BreakGlassLifecycleStatus $status, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $required = $status !== null && in_array($status, [BreakGlassLifecycleStatus::UnderReview, BreakGlassLifecycleStatus::Closed], true);
        if (! is_array($review)) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_review', "{$title} lacks an independent retrospective review.");
            }

            return false;
        }
        $valid = $this->completeRecord($review, ['reviewed_by_key', 'reviewed_by', 'reviewed_at', 'necessity', 'authority', 'scope_adherence', 'activity_assessment', 'outcome', 'credential_handling', 'control_performance', 'evidence_record_key'])
            && ($review['blameless'] ?? false) === true;
        if (($review['reviewed_by_key'] ?? null) === $actorKey) {
            $conflicts[] = $this->issue('self_reviewed_break_glass', "{$title} actor cannot independently review their own emergency access.");
            $valid = false;
        }
        if (! $valid) {
            $decisionGaps[] = $this->issue('invalid_break_glass_review', "{$title} has an incomplete independent retrospective review.");
        }
        $this->requireEvidence($review['evidence_record_key'] ?? null, $evidenceKeys, "{$title} review", $evidenceGaps);

        return $required && $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateCorrectiveActions(string $title, mixed $actions, ?BreakGlassLifecycleStatus $status, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $required = $status !== null && in_array($status, [BreakGlassLifecycleStatus::UnderReview, BreakGlassLifecycleStatus::Closed], true);
        if (! is_array($actions) || $actions === []) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_break_glass_corrective_action', "{$title} lacks an owned corrective action or evidenced no-action decision.");
            }

            return false;
        }
        $valid = true;
        foreach ($actions as $action) {
            if (! is_array($action) || ! $this->completeRecord($action, ['key', 'description', 'owner', 'due_at', 'status'])) {
                $decisionGaps[] = $this->issue('unaccountable_break_glass_corrective_action', "{$title} has a corrective action without an owner, due date, or status.");
                $valid = false;

                continue;
            }
            if (in_array($action['status'], ['verified', 'closed'], true)) {
                $this->requireEvidence($action['evidence_record_key'] ?? null, $evidenceKeys, "{$title} completed corrective action", $evidenceGaps);
            }
        }

        return $required && $valid;
    }

    /**
     * @param  list<array{key: string, label: string, question: string}>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateRequirements(array $requirements, array &$conflicts): void
    {
        $keys = [];
        foreach ($requirements as $requirement) {
            if ($requirement['key'] === '' || in_array($requirement['key'], $keys, true) || $requirement['label'] === '' || $requirement['question'] === '') {
                $conflicts[] = $this->issue('invalid_break_glass_requirement', 'A Break-glass Access requirement is missing, incomplete, or duplicated.');
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
    private function validateEvidence(array $records, array &$conflicts, array &$evidenceGaps): array
    {
        $keys = [];
        $validKeys = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $unique = $key !== '' && ! in_array($key, $keys, true);
            if (! $unique) {
                $conflicts[] = $this->issue('invalid_break_glass_evidence_key', 'A Break-glass Evidence Record has a missing or duplicate key.');
            }
            $complete = $this->completeRecord($record, ['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state']);
            if (! $complete) {
                $evidenceGaps[] = $this->issue('incomplete_break_glass_evidence', "Evidence Record {$key} is incomplete.");
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
    private function resolvePolicies(BreakGlassAccessDefinition $definition, ResolvedPolicyRegistry $registry, Carbon $asOf, array &$conflicts, array &$readinessGaps): array
    {
        $resolved = [];
        $allOperative = true;
        $policyEvidenceKeys = array_column($registry->evidenceRecords, 'key');
        foreach ($definition->governingPolicies as $reference) {
            $policy = $this->findByKey($registry->policies, $reference['key']);
            $version = is_array($policy) ? $this->findVersion($policy['versions'] ?? null, $reference['version']) : null;
            if (! is_array($policy) || ! is_array($version)) {
                $conflicts[] = $this->issue('missing_break_glass_policy', "Break-glass Access references missing policy {$reference['key']} version {$reference['version']}.");
                $status = null;
                $operative = false;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $effectiveAt = $this->date($version['effective_at'] ?? null);
                $operative = $status === PolicyLifecycleStatus::Effective
                    && ($version['content_integrity'] ?? null) === 'verified'
                    && $effectiveAt !== null
                    && $effectiveAt->lessThanOrEqualTo($asOf)
                    && is_array($approval)
                    && ($approval['outcome'] ?? null) === 'approved'
                    && in_array($approval['evidence_record_key'] ?? null, $policyEvidenceKeys, true);
            }
            if ($reference['required_for_activation'] && ! $operative) {
                $allOperative = false;
                $readinessGaps[] = $this->issue('break_glass_policy_not_operative', "{$reference['key']} version {$reference['version']} is not operative for emergency activation.");
            }
            $resolved[] = array_merge($reference, [
                'title' => $policy['title'] ?? $reference['key'],
                'status' => $status instanceof PolicyLifecycleStatus ? $status->value : 'missing',
                'status_label' => $status instanceof PolicyLifecycleStatus ? $status->label() : 'Missing',
                'operative' => $operative,
            ]);
        }

        return [$resolved, $allOperative];
    }

    /** @return array<string, array<string, mixed>> */
    private function openEngagements(ResolvedEngagements $engagements): array
    {
        $resolved = [];
        foreach ($engagements->engagements as $engagement) {
            if (($engagement['may_perform_client_work'] ?? false) === true && is_string($engagement['key'] ?? null)) {
                $resolved[$engagement['key']] = $engagement;
            }
        }

        return $resolved;
    }

    /** @return array<string, array<string, mixed>> */
    private function declaredIncidents(ResolvedIncidents $incidents): array
    {
        $resolved = [];
        foreach ($incidents->incidentRecords as $incident) {
            if (
                ! in_array($incident['lifecycle_status'] ?? null, ['detected', 'triaged', 'false_positive'], true)
                && is_string($incident['key'] ?? null)
            ) {
                $resolved[$incident['key']] = $incident;
            }
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, mixed>|null
     */
    private function findByKey(array $records, string $key): ?array
    {
        foreach ($records as $record) {
            if (($record['key'] ?? null) === $key) {
                return $record;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function findVersion(mixed $versions, string $version): ?array
    {
        if (! is_array($versions)) {
            return null;
        }
        foreach ($versions as $candidate) {
            if (is_array($candidate) && ($candidate['version'] ?? null) === $version) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function requireEvidence(mixed $key, array $evidenceKeys, string $subject, array &$evidenceGaps): void
    {
        if (! is_string($key) || ! in_array($key, $evidenceKeys, true)) {
            $evidenceGaps[] = $this->issue('missing_break_glass_evidence', "{$subject} does not reference a complete Evidence Record.");
        }
    }

    /** @param list<string> $fields */
    private function completeRecord(mixed $record, array $fields): bool
    {
        if (! is_array($record)) {
            return false;
        }
        foreach ($fields as $field) {
            if (! array_key_exists($field, $record) || $record[$field] === '' || $record[$field] === null || $record[$field] === []) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $record */
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

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }
}
