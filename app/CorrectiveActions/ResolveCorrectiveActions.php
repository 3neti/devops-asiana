<?php

namespace App\CorrectiveActions;

use App\BreakGlassAccess\ResolvedBreakGlassAccess;
use App\Changes\ResolvedChanges;
use App\Incidents\ResolvedIncidents;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use App\ProductionAccess\ResolvedProductionAccess;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveCorrectiveActions
{
    public function handle(
        CorrectiveActionDefinition $definition,
        ResolvedIncidents $incidents,
        ResolvedChanges $changes,
        ResolvedBreakGlassAccess $breakGlassAccess,
        ResolvedProductionAccess $productionAccess,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedCorrectiveActions {
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
            static fn (CorrectiveActionLifecycleStatus $status): string => $status->value,
            CorrectiveActionLifecycleStatus::cases(),
        ), 0);

        $this->validateRequirements($definition->recordRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        $governingPolicies = $this->resolvePolicies($definition, $policyRegistry, $effectiveAt, $conflicts, $readinessGaps);
        $sources = $this->sourceIndex($incidents, $changes, $breakGlassAccess, $productionAccess, $policyRegistry);
        /** @var list<string> $recordKeys */
        $recordKeys = [];
        /** @var list<array<string, mixed>> $resolvedActions */
        $resolvedActions = [];

        foreach ($definition->correctiveActions as $action) {
            $resolvedActions[] = $this->resolveAction(
                action: $action,
                sources: $sources,
                policies: $governingPolicies,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                recordKeys: $recordKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedCorrectiveActions(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            recordRequirements: $definition->recordRequirements,
            correctiveActions: $resolvedActions,
            evidenceRecords: $definition->evidenceRecords,
            lifecycleCounts: $lifecycleCounts,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
            readinessGaps: $readinessGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, array<string, array<string, mixed>>>  $sources
     * @param  list<array<string, mixed>>  $policies
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $recordKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<string> $recordKeys
     * @param-out array<string, int> $lifecycleCounts
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return array<string, mixed>
     */
    private function resolveAction(
        array $action,
        array $sources,
        array $policies,
        array $evidenceKeys,
        Carbon $asOf,
        array &$recordKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $key = (string) ($action['key'] ?? '');
        $title = (string) ($action['title'] ?? $key);
        $status = CorrectiveActionLifecycleStatus::tryFrom((string) ($action['lifecycle_status'] ?? ''));
        $source = is_array($action['source'] ?? null) ? $action['source'] : [];
        $sourceType = CorrectiveActionSourceType::tryFrom((string) ($source['type'] ?? ''));

        if ($key === '' || in_array($key, $recordKeys, true)) {
            $conflicts[] = $this->issue('invalid_corrective_action_key', 'A Corrective Action has a missing or duplicate key.');
        }
        $recordKeys[] = $key;
        if ($status === null) {
            $conflicts[] = $this->issue('invalid_corrective_action_lifecycle', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }
        if ($sourceType === null) {
            $conflicts[] = $this->issue('invalid_corrective_action_source_type', "{$title} has an invalid source type.");
        }

        $sourceResolved = $this->validateSource($title, $source, $sourceType, $sources, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $requirement = is_array($action['governing_requirement'] ?? null) ? $action['governing_requirement'] : [];
        $policyOperative = $this->validateGoverningRequirement($title, $requirement, $sourceType, $policies, $decisionGaps);
        $riskValid = $this->completeRecord($action['risk'] ?? null, ['classification', 'impact', 'residual_risk_if_delayed', 'risk_owner']);
        if (! $riskValid) {
            $decisionGaps[] = $this->issue('missing_corrective_action_risk', "{$title} lacks explicit risk, impact, delay exposure, or risk ownership.");
        }

        $requiresAssignment = $status !== null && ! in_array($status, [CorrectiveActionLifecycleStatus::Proposed, CorrectiveActionLifecycleStatus::Cancelled], true);
        $owner = is_array($action['owner'] ?? null) ? $action['owner'] : [];
        $ownerValid = $this->completeRecord($owner, ['key', 'name', 'role']);
        $assignment = is_array($action['assignment'] ?? null) ? $action['assignment'] : [];
        $assignmentValid = $this->completeRecord($assignment, ['assigned_by', 'authority_basis', 'accepted_by', 'assigned_at', 'evidence_record_key']);
        if ($requiresAssignment && (! $ownerValid || ! $assignmentValid)) {
            $decisionGaps[] = $this->issue('unassigned_corrective_action', "{$title} has reached assignment without exactly one accountable owner and an explicit accepted assignment.");
        }
        if ($requiresAssignment) {
            $this->requireEvidence($assignment['evidence_record_key'] ?? null, $evidenceKeys, "{$title} assignment", $evidenceGaps);
        }

        $plan = is_array($action['remediation_plan'] ?? null) ? $action['remediation_plan'] : [];
        $planValid = $this->completeRecord($plan, ['outcome', 'acceptance_criteria', 'steps']);
        if ($requiresAssignment && ! $planValid) {
            $decisionGaps[] = $this->issue('missing_remediation_plan', "{$title} lacks a bounded remediation outcome, acceptance criteria, or steps.");
        }

        [$dueAt, $dueHistoryValid] = $this->validateDueDateHistory($title, $action['due_date_history'] ?? null, $requiresAssignment, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $requiresProgress = $status !== null && in_array($status, [CorrectiveActionLifecycleStatus::InProgress, CorrectiveActionLifecycleStatus::PendingVerification, CorrectiveActionLifecycleStatus::Verified, CorrectiveActionLifecycleStatus::Closed], true);
        $this->validateProgress($title, $action['progress_updates'] ?? null, $requiresProgress, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $terminal = $status !== null && in_array($status, [CorrectiveActionLifecycleStatus::Verified, CorrectiveActionLifecycleStatus::Closed, CorrectiveActionLifecycleStatus::Cancelled, CorrectiveActionLifecycleStatus::Superseded], true);
        $overdue = $requiresAssignment && ! $terminal && $dueAt !== null && $asOf->greaterThan($dueAt);
        $escalationValid = $this->validateEscalation($title, $action['escalation'] ?? null, $overdue, $evidenceKeys, $decisionGaps, $evidenceGaps);

        $completionRequired = $status !== null && in_array($status, [CorrectiveActionLifecycleStatus::PendingVerification, CorrectiveActionLifecycleStatus::Verified, CorrectiveActionLifecycleStatus::Closed], true);
        $completion = is_array($action['completion_claim'] ?? null) ? $action['completion_claim'] : [];
        $completionValid = $this->completeRecord($completion, ['claimed_by_key', 'claimed_by', 'summary', 'claimed_at', 'evidence_record_key']);
        if ($completionRequired && ! $completionValid) {
            $decisionGaps[] = $this->issue('missing_completion_claim', "{$title} reached verification without an explicit completion claim.");
        }
        if ($completionRequired && $completionValid && ($completion['claimed_by_key'] ?? null) !== ($owner['key'] ?? null)) {
            $conflicts[] = $this->issue('completion_claim_not_by_owner', "{$title} completion is claimed by someone other than its accountable owner.");
            $completionValid = false;
        }
        if ($completionRequired) {
            $this->requireEvidence($completion['evidence_record_key'] ?? null, $evidenceKeys, "{$title} completion claim", $evidenceGaps);
        }

        $verificationRequired = $status !== null && in_array($status, [CorrectiveActionLifecycleStatus::Verified, CorrectiveActionLifecycleStatus::Closed], true);
        $verification = is_array($action['verification'] ?? null) ? $action['verification'] : [];
        $verificationValid = $this->completeRecord($verification, ['verified_by_key', 'verified_by', 'verification_standard', 'observed_result', 'outcome', 'verified_at', 'evidence_record_key'])
            && ($verification['outcome'] ?? null) === 'verified';
        if ($verificationRequired && ! $verificationValid) {
            $decisionGaps[] = $this->issue('missing_independent_verification', "{$title} reached Verified or Closed without successful independent verification.");
        }
        if ($verificationRequired && ($owner['key'] ?? null) === ($verification['verified_by_key'] ?? null)) {
            $conflicts[] = $this->issue('self_verified_corrective_action', "{$title} is verified by its accountable owner; completion and verification must be independent.");
            $verificationValid = false;
        }
        $completionAt = $this->date($completion['claimed_at'] ?? null);
        $verificationAt = $this->date($verification['verified_at'] ?? null);
        if ($verificationRequired && ($completionAt === null || $verificationAt === null || $verificationAt->lessThanOrEqualTo($completionAt))) {
            $conflicts[] = $this->issue('invalid_corrective_action_verification_sequence', "{$title} verification must occur after the owner's valid completion claim.");
            $verificationValid = false;
        }
        if ($verificationRequired) {
            $this->requireEvidence($verification['evidence_record_key'] ?? null, $evidenceKeys, "{$title} verification", $evidenceGaps);
        }

        $mayClose = $status === CorrectiveActionLifecycleStatus::Verified
            && $sourceResolved && $policyOperative && $riskValid && $ownerValid && $assignmentValid && $planValid
            && $dueHistoryValid && $completionValid && $verificationValid;
        $closure = is_array($action['closure'] ?? null) ? $action['closure'] : [];
        $closureValid = $this->completeRecord($closure, ['closed_by', 'authority_basis', 'reason', 'closed_at', 'evidence_record_key']);
        if ($status === CorrectiveActionLifecycleStatus::Closed && (! $closureValid || ! $verificationValid)) {
            $conflicts[] = $this->issue('invalid_corrective_action_closure', "{$title} is Closed without a distinct authorized closure after successful verification.");
        }
        $closedAt = $this->date($closure['closed_at'] ?? null);
        if ($status === CorrectiveActionLifecycleStatus::Closed && ($closedAt === null || $verificationAt === null || $closedAt->lessThanOrEqualTo($verificationAt))) {
            $conflicts[] = $this->issue('invalid_corrective_action_closure_sequence', "{$title} closure must occur after successful independent verification.");
            $closureValid = false;
        }
        if ($status === CorrectiveActionLifecycleStatus::Closed) {
            $this->requireEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys, "{$title} closure", $evidenceGaps);
        }

        if ($status !== null && in_array($status, [CorrectiveActionLifecycleStatus::Cancelled, CorrectiveActionLifecycleStatus::Superseded], true)) {
            $disposition = is_array($action['disposition'] ?? null) ? $action['disposition'] : [];
            if (! $this->completeRecord($disposition, ['decided_by', 'authority_basis', 'reason', 'decided_at', 'evidence_record_key'])) {
                $decisionGaps[] = $this->issue('missing_corrective_action_disposition', "{$title} is {$status->label()} without an explicit authorized disposition.");
            }
            $this->requireEvidence($disposition['evidence_record_key'] ?? null, $evidenceKeys, "{$title} disposition", $evidenceGaps);
        }

        return array_merge($action, [
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'source_type_label' => $sourceType?->label() ?? 'Invalid',
            'source_resolved' => $sourceResolved,
            'governing_policy_operative' => $policyOperative,
            'current_due_at' => $dueAt?->toIso8601String(),
            'overdue' => $overdue,
            'escalation_current' => ! $overdue || $escalationValid,
            'may_close_corrective_action' => $mayClose,
            'operational_status' => $this->operationalStatus($status, $overdue, $verificationValid, $closureValid),
        ]);
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, array<string, array<string, mixed>>>  $sources
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function validateSource(string $title, array $source, ?CorrectiveActionSourceType $type, array $sources, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $complete = $this->completeRecord($source, ['type', 'key', 'finding', 'identified_by', 'found_at', 'evidence_record_key']);
        if (! $complete) {
            $decisionGaps[] = $this->issue('missing_corrective_action_source', "{$title} lacks a complete attributable source finding.");
        }
        $this->requireEvidence($source['evidence_record_key'] ?? null, $evidenceKeys, "{$title} source finding", $evidenceGaps);
        if ($type === null || ! $complete) {
            return false;
        }
        if ($type === CorrectiveActionSourceType::OtherFinding) {
            return true;
        }
        if (! isset($sources[$type->value][(string) $source['key']])) {
            $conflicts[] = $this->issue('unresolved_corrective_action_source', "{$title} references a {$type->label()} that is absent from resolved institutional records.");

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $requirement
     * @param  list<array<string, mixed>>  $policies
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateGoverningRequirement(string $title, array $requirement, ?CorrectiveActionSourceType $sourceType, array $policies, array &$decisionGaps): bool
    {
        if (! $this->completeRecord($requirement, ['policy_key', 'policy_version', 'requirement', 'control'])) {
            $decisionGaps[] = $this->issue('missing_governing_requirement', "{$title} does not identify the policy requirement and control being remediated.");

            return false;
        }
        foreach ($policies as $policy) {
            if (($policy['key'] ?? null) === $requirement['policy_key']
                && ($policy['version'] ?? null) === $requirement['policy_version']
                && ($sourceType === null || in_array('all', $policy['applies_to'] ?? [], true) || in_array($sourceType->value, $policy['applies_to'] ?? [], true))) {
                if (! ($policy['operative'] ?? false)) {
                    $decisionGaps[] = $this->issue('inoperative_corrective_action_policy', "{$title} is governed by a policy version that is not operative.");
                }

                return ($policy['operative'] ?? false) === true;
            }
        }
        $decisionGaps[] = $this->issue('unrecognized_governing_requirement', "{$title} references a policy version that is not configured for its source type.");

        return false;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return array{?Carbon, bool}
     */
    private function validateDueDateHistory(string $title, mixed $history, bool $required, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! is_array($history) || $history === []) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_corrective_action_due_date', "{$title} has no explicit due-date history.");
            }

            return [null, ! $required];
        }
        $lastDue = null;
        $lastChanged = null;
        $valid = true;
        foreach (array_values($history) as $index => $entry) {
            $complete = $this->completeRecord($entry, ['effective_due_at', 'changed_by', 'authority_basis', 'reason', 'changed_at', 'evidence_record_key']);
            $due = $this->date(is_array($entry) ? ($entry['effective_due_at'] ?? null) : null);
            $changed = $this->date(is_array($entry) ? ($entry['changed_at'] ?? null) : null);
            if (! $complete || $due === null || $changed === null) {
                $conflicts[] = $this->issue('invalid_due_date_history', "{$title} has an incomplete or invalid due-date history entry.");
                $valid = false;

                continue;
            }
            if ($lastChanged !== null && $changed->lessThanOrEqualTo($lastChanged)) {
                $conflicts[] = $this->issue('unordered_due_date_history', "{$title} due-date history is not strictly chronological.");
                $valid = false;
            }
            if ($index > 0) {
                $this->requireEvidence($entry['evidence_record_key'], $evidenceKeys, "{$title} due-date change", $evidenceGaps);
            } else {
                $this->requireEvidence($entry['evidence_record_key'], $evidenceKeys, "{$title} initial due date", $evidenceGaps);
            }
            $lastDue = $due;
            $lastChanged = $changed;
        }

        return [$lastDue, $valid];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function validateEscalation(string $title, mixed $escalation, bool $overdue, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $overdue) {
            return true;
        }
        $valid = $this->completeRecord($escalation, ['escalated_by', 'authority_basis', 'audience', 'reason', 'escalated_at', 'evidence_record_key']);
        if (! $valid) {
            $decisionGaps[] = $this->issue('overdue_corrective_action_not_escalated', "{$title} is overdue without an explicit escalation record.");
        }
        $this->requireEvidence(is_array($escalation) ? ($escalation['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} overdue escalation", $evidenceGaps);

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function validateProgress(string $title, mixed $updates, bool $required, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! is_array($updates) || $updates === []) {
            if ($required) {
                $decisionGaps[] = $this->issue('missing_corrective_action_progress', "{$title} has reached active remediation without an attributable progress record.");
            }

            return ! $required;
        }
        $keys = [];
        $lastRecordedAt = null;
        $valid = true;
        foreach (array_values($updates) as $update) {
            $key = is_array($update) ? (string) ($update['key'] ?? '') : '';
            $recordedAt = $this->date(is_array($update) ? ($update['recorded_at'] ?? null) : null);
            if (! $this->completeRecord($update, ['key', 'actor', 'update', 'recorded_at', 'evidence_record_key']) || $recordedAt === null || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_corrective_action_progress', "{$title} has an incomplete, duplicated, or invalid progress entry.");
                $valid = false;

                continue;
            }
            if ($lastRecordedAt !== null && $recordedAt->lessThanOrEqualTo($lastRecordedAt)) {
                $conflicts[] = $this->issue('unordered_corrective_action_progress', "{$title} progress history is not strictly chronological.");
                $valid = false;
            }
            $this->requireEvidence($update['evidence_record_key'], $evidenceKeys, "{$title} progress update", $evidenceGaps);
            $keys[] = $key;
            $lastRecordedAt = $recordedAt;
        }

        return $valid;
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function sourceIndex(ResolvedIncidents $incidents, ResolvedChanges $changes, ResolvedBreakGlassAccess $breakGlassAccess, ResolvedProductionAccess $productionAccess, ResolvedPolicyRegistry $policies): array
    {
        return [
            CorrectiveActionSourceType::Incident->value => $this->indexByKey($incidents->incidentRecords),
            CorrectiveActionSourceType::Change->value => $this->indexByKey($changes->changeRecords),
            CorrectiveActionSourceType::BreakGlassAccess->value => $this->indexByKey($breakGlassAccess->accessRecords),
            CorrectiveActionSourceType::ProductionAccess->value => $this->indexByKey($productionAccess->accessGrants),
            CorrectiveActionSourceType::PolicyException->value => $this->indexByKey($policies->exceptions),
            CorrectiveActionSourceType::OtherFinding->value => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $indexed = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null) && $record['key'] !== '') {
                $indexed[$record['key']] = $record;
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $readinessGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $readinessGaps
     *
     * @return list<array<string, mixed>>
     */
    private function resolvePolicies(CorrectiveActionDefinition $definition, ResolvedPolicyRegistry $registry, Carbon $asOf, array &$conflicts, array &$readinessGaps): array
    {
        $resolved = [];
        $evidenceKeys = array_column($registry->evidenceRecords, 'key');
        foreach ($definition->governingPolicies as $reference) {
            $policy = null;
            foreach ($registry->policies as $candidate) {
                if (($candidate['key'] ?? null) === $reference['key']) {
                    $policy = $candidate;
                    break;
                }
            }
            $version = null;
            if (is_array($policy)) {
                foreach ($policy['versions'] ?? [] as $candidateVersion) {
                    if (is_array($candidateVersion) && ($candidateVersion['version'] ?? null) === $reference['version']) {
                        $version = $candidateVersion;
                        break;
                    }
                }
            }
            if (! is_array($policy) || ! is_array($version)) {
                $conflicts[] = $this->issue('missing_corrective_action_policy', "Corrective Actions reference missing policy {$reference['key']} version {$reference['version']}.");
                $status = null;
                $operative = false;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $effectiveAt = $this->date($version['effective_at'] ?? null);
                $operative = $status === PolicyLifecycleStatus::Effective
                    && ($version['content_integrity'] ?? null) === 'verified'
                    && $effectiveAt !== null && $effectiveAt->lessThanOrEqualTo($asOf)
                    && is_array($approval) && ($approval['outcome'] ?? null) === 'approved'
                    && in_array($approval['evidence_record_key'] ?? null, $evidenceKeys, true);
            }
            if ($reference['required_for_assignment'] && ! $operative) {
                $readinessGaps[] = $this->issue('corrective_action_policy_not_operative', "{$reference['key']} version {$reference['version']} is not operative for Corrective Action assignment.");
            }
            $resolved[] = array_merge($reference, [
                'title' => $policy['title'] ?? $reference['key'],
                'status' => $status instanceof PolicyLifecycleStatus ? $status->value : 'missing',
                'status_label' => $status instanceof PolicyLifecycleStatus ? $status->label() : 'Missing',
                'operative' => $operative,
            ]);
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return list<string>
     */
    private function validateEvidence(array $evidence, array &$conflicts, array &$evidenceGaps): array
    {
        $keys = [];
        foreach ($evidence as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_corrective_action_evidence_key', 'A Corrective Action Evidence Record has a missing or duplicate key.');
            }
            $complete = $this->completeRecord($record, ['key', 'record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state']);
            if (! $complete || ($record['state'] ?? null) !== 'final' || $this->date($record['recorded_at'] ?? null) === null) {
                $evidenceGaps[] = $this->issue('incomplete_corrective_action_evidence', "Evidence {$key} is incomplete, invalid, or not final.");
            } else {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param  list<array{key: string, label: string, question: string}>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     */
    private function validateRequirements(array $requirements, array &$conflicts): void
    {
        $keys = [];
        foreach ($requirements as $requirement) {
            if (empty($requirement['key']) || in_array($requirement['key'], $keys, true) || empty($requirement['label']) || empty($requirement['question'])) {
                $conflicts[] = $this->issue('invalid_corrective_action_requirement', 'A Corrective Action requirement is missing, incomplete, or duplicated.');
            }
            $keys[] = $requirement['key'];
        }
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function requireEvidence(mixed $key, array $evidenceKeys, string $subject, array &$evidenceGaps): void
    {
        if (! is_string($key) || ! in_array($key, $evidenceKeys, true)) {
            $evidenceGaps[] = $this->issue('missing_corrective_action_evidence', "{$subject} does not reference a complete Evidence Record.");
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

    private function operationalStatus(?CorrectiveActionLifecycleStatus $status, bool $overdue, bool $verificationValid, bool $closureValid): string
    {
        return match (true) {
            $status === CorrectiveActionLifecycleStatus::Closed && $closureValid && $verificationValid => 'closed_verified',
            $status === CorrectiveActionLifecycleStatus::Closed => 'blocked_closure',
            $status === CorrectiveActionLifecycleStatus::Cancelled => 'cancelled',
            $status === CorrectiveActionLifecycleStatus::Superseded => 'superseded',
            $overdue => 'overdue',
            $status === CorrectiveActionLifecycleStatus::PendingVerification => 'awaiting_verification',
            $status === CorrectiveActionLifecycleStatus::Verified && $verificationValid => 'ready_for_closure',
            $status === CorrectiveActionLifecycleStatus::InProgress => 'active',
            $status === CorrectiveActionLifecycleStatus::Assigned => 'assigned',
            default => 'proposed',
        };
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
