<?php

namespace App\Continuity;

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveContinuityExercises
{
    public function handle(
        ContinuityExerciseDefinition $definition,
        ResolvedEngagements $engagements,
        ResolvedCorrectiveActions $correctiveActions,
        ResolvedPolicyRegistry $policyRegistry,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedContinuityExercises {
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
            static fn (ContinuityExerciseLifecycleStatus $status): string => $status->value,
            ContinuityExerciseLifecycleStatus::cases(),
        ), 0);

        $this->validateRequirements($definition->recordRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $policiesOperative] = $this->resolvePolicies($definition, $policyRegistry, $effectiveAt, $conflicts, $readinessGaps);
        $openEngagements = $this->indexByKey(array_values(array_filter(
            $engagements->engagements,
            static fn (array $engagement): bool => ($engagement['may_perform_client_work'] ?? false) === true,
        )));
        $correctiveActionIndex = $this->indexByKey($correctiveActions->correctiveActions);
        /** @var list<string> $recordKeys */
        $recordKeys = [];
        /** @var list<array<string, mixed>> $resolvedRecords */
        $resolvedRecords = [];

        foreach ($definition->exerciseRecords as $record) {
            $resolvedRecords[] = $this->resolveRecord(
                record: $record,
                policiesOperative: $policiesOperative,
                openEngagements: $openEngagements,
                correctiveActions: $correctiveActionIndex,
                evidenceKeys: $evidenceKeys,
                asOf: $effectiveAt,
                recordKeys: $recordKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedContinuityExercises(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            recordRequirements: $definition->recordRequirements,
            exerciseRecords: $resolvedRecords,
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
     * @param  array<string, array<string, mixed>>  $correctiveActions
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
    private function resolveRecord(
        array $record,
        bool $policiesOperative,
        array $openEngagements,
        array $correctiveActions,
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
        $status = ContinuityExerciseLifecycleStatus::tryFrom((string) ($record['lifecycle_status'] ?? ''));
        $type = ContinuityExerciseType::tryFrom((string) ($record['exercise_type'] ?? ''));

        if ($key === '' || in_array($key, $recordKeys, true)) {
            $conflicts[] = $this->issue('invalid_continuity_exercise_key', 'A Continuity Exercise has a missing or duplicate key.');
        }
        $recordKeys[] = $key;
        if ($status === null) {
            $conflicts[] = $this->issue('invalid_continuity_lifecycle', "{$title} has an invalid lifecycle status.");
        } else {
            $lifecycleCounts[$status->value]++;
        }
        if ($type === null) {
            $conflicts[] = $this->issue('invalid_continuity_exercise_type', "{$title} has an invalid exercise type.");
        }

        $requiresApproval = $status !== null && ! in_array($status, [ContinuityExerciseLifecycleStatus::Proposed, ContinuityExerciseLifecycleStatus::Cancelled], true);
        $scopeValid = $this->validateScope($title, $record['scope'] ?? null, $requiresApproval, $openEngagements, $conflicts, $decisionGaps);
        [$objectivesValid, $objectives] = $this->validateObjectives($title, $record['recovery_objectives'] ?? null, $requiresApproval, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $dependenciesValid = $this->validateDependencies($title, $record['dependencies'] ?? null, $requiresApproval, $decisionGaps);
        $planValid = $this->validatePlan($title, $record['exercise_plan'] ?? null, $requiresApproval, $decisionGaps);
        $approvalValid = $this->validateApproval($title, $record['approval'] ?? null, $requiresApproval, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $backupRequired = $requiresApproval && $type?->requiresRestoreEvidence() === true;
        $backupValid = $this->validateBackupBaseline($title, $record['backup_baseline'] ?? null, $backupRequired, $evidenceKeys, $decisionGaps, $evidenceGaps);
        [$windowState, $windowValid] = $this->validateSchedule($title, $record['schedule'] ?? null, $requiresApproval, $asOf, $decisionGaps);

        $mayExecute = $status === ContinuityExerciseLifecycleStatus::Scheduled
            && $policiesOperative && $scopeValid && $objectivesValid && $dependenciesValid && $planValid
            && $approvalValid && $backupValid && $windowValid && $windowState === 'within_window';

        $executionRequired = $status !== null && in_array($status, [ContinuityExerciseLifecycleStatus::InProgress, ContinuityExerciseLifecycleStatus::AwaitingVerification, ContinuityExerciseLifecycleStatus::Verified, ContinuityExerciseLifecycleStatus::Closed], true);
        $executionCompleteRequired = $status !== null && in_array($status, [ContinuityExerciseLifecycleStatus::AwaitingVerification, ContinuityExerciseLifecycleStatus::Verified, ContinuityExerciseLifecycleStatus::Closed], true);
        [$executionValid, $coordinatorKey, $executionCompletedAt] = $this->validateExecution($title, $record['execution'] ?? null, $executionRequired, $executionCompleteRequired, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);

        $resultRequired = $executionCompleteRequired && $type?->requiresRestoreEvidence() === true;
        [$restoreValid, $observations, $cleanupValid] = $this->validateRestoreResult($title, $record['restore_result'] ?? null, $resultRequired, $objectives, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $objectiveSummary = $this->objectiveSummary($objectives, $observations);

        $verificationRequired = $status !== null && in_array($status, [ContinuityExerciseLifecycleStatus::Verified, ContinuityExerciseLifecycleStatus::Closed], true);
        [$verificationValid, $unresolvedGaps, $verifiedAt] = $this->validateVerification(
            $title,
            $record['verification'] ?? null,
            $verificationRequired,
            $coordinatorKey,
            $executionCompletedAt,
            $objectiveSummary['missed'],
            $correctiveActions,
            $evidenceKeys,
            $conflicts,
            $decisionGaps,
            $evidenceGaps,
        );

        $mayClose = $status === ContinuityExerciseLifecycleStatus::Verified
            && $policiesOperative && $scopeValid && $objectivesValid && $dependenciesValid && $planValid
            && $approvalValid && $backupValid && $executionValid && $restoreValid && $cleanupValid
            && $verificationValid && $unresolvedGaps === 0;
        $closure = is_array($record['closure'] ?? null) ? $record['closure'] : [];
        $closureValid = $this->completeRecord($closure, ['closed_by', 'authority_basis', 'result_accepted', 'communications_completed', 'closed_at', 'evidence_record_key']);
        $closedAt = $this->date($closure['closed_at'] ?? null);
        if ($status === ContinuityExerciseLifecycleStatus::Closed && (! $closureValid || ! $verificationValid || $unresolvedGaps > 0 || $closedAt === null || $verifiedAt === null || $closedAt->lessThanOrEqualTo($verifiedAt))) {
            $conflicts[] = $this->issue('invalid_continuity_exercise_closure', "{$title} is Closed without verified results, resolved gap accountability, chronological authority, and evidence.");
            $closureValid = false;
        }
        if ($status === ContinuityExerciseLifecycleStatus::Closed) {
            $this->requireEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys, "{$title} closure", $evidenceGaps);
        }
        if ($status === ContinuityExerciseLifecycleStatus::Cancelled) {
            $disposition = is_array($record['disposition'] ?? null) ? $record['disposition'] : [];
            if (! $this->completeRecord($disposition, ['decided_by', 'authority_basis', 'reason', 'decided_at', 'evidence_record_key'])) {
                $decisionGaps[] = $this->issue('missing_continuity_cancellation', "{$title} is Cancelled without an explicit authorized disposition.");
            }
            $this->requireEvidence($disposition['evidence_record_key'] ?? null, $evidenceKeys, "{$title} cancellation", $evidenceGaps);
        }

        return array_merge($record, [
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'exercise_type_label' => $type?->label() ?? 'Invalid',
            'window_state' => $windowState,
            'may_execute_exercise' => $mayExecute,
            'may_close_exercise' => $mayClose,
            'objectives_met' => $objectiveSummary['met'],
            'objectives_missed' => $objectiveSummary['missed'],
            'objectives_not_measured' => $objectiveSummary['not_measured'],
            'unresolved_gaps' => $unresolvedGaps,
            'operational_status' => $this->operationalStatus($status, $mayExecute, $verificationValid, $closureValid, $unresolvedGaps),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     */
    private function validateScope(string $title, mixed $scope, bool $required, array $openEngagements, array &$conflicts, array &$decisionGaps): bool
    {
        if (! $required) {
            return true;
        }
        $valid = $this->completeRecord($scope, ['context', 'services', 'systems', 'environments', 'data_classification', 'exclusions', 'scope_owner'])
            && in_array($scope['context'] ?? null, ['firm', 'client'], true);
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_continuity_scope', "{$title} lacks a complete Firm or Client exercise scope.");

            return false;
        }
        if ($scope['context'] === 'client') {
            $engagementKey = is_string($scope['engagement_key'] ?? null) ? $scope['engagement_key'] : '';
            $engagement = $openEngagements[$engagementKey] ?? null;
            if ($engagement === null) {
                $conflicts[] = $this->issue('continuity_without_open_engagement', "{$title} is Client-scoped without an Open Engagement.");

                return false;
            }
            $mandate = $engagement['client_mandate'] ?? [];
            $insideMandate = $this->valuesWithin($scope['systems'], $mandate['systems'] ?? null)
                && $this->valuesWithin($scope['environments'], $mandate['environments'] ?? null)
                && in_array('Continuity exercise', $mandate['permitted_actions'] ?? [], true);
            if (! $insideMandate) {
                $conflicts[] = $this->issue('continuity_outside_client_mandate', "{$title} scope is outside the Engagement Client Mandate.");
            }

            return $insideMandate;
        }

        return true;
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
     * @return array{bool, array<string, array<string, mixed>>}
     */
    private function validateObjectives(string $title, mixed $objectives, bool $required, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! $required) {
            return [true, []];
        }
        if (! is_array($objectives) || $objectives === []) {
            $decisionGaps[] = $this->issue('missing_recovery_objectives', "{$title} has no explicit approved recovery objectives.");

            return [false, []];
        }
        $indexed = [];
        $valid = true;
        foreach ($objectives as $objective) {
            $key = is_array($objective) ? (string) ($objective['key'] ?? '') : '';
            $complete = $this->completeRecord($objective, ['key', 'service', 'rto_seconds', 'rpo_seconds', 'source', 'approved_by', 'approved_at', 'evidence_record_key']);
            $positive = is_int($objective['rto_seconds'] ?? null) && $objective['rto_seconds'] > 0
                && is_int($objective['rpo_seconds'] ?? null) && $objective['rpo_seconds'] >= 0;
            if (! $complete || ! $positive || isset($indexed[$key]) || $this->date($objective['approved_at'] ?? null) === null) {
                $conflicts[] = $this->issue('invalid_recovery_objective', "{$title} has an incomplete, duplicated, unapproved, or invalid RTO/RPO objective.");
                $valid = false;

                continue;
            }
            $this->requireEvidence($objective['evidence_record_key'], $evidenceKeys, "{$title} recovery objective {$key}", $evidenceGaps);
            $indexed[$key] = $objective;
        }

        return [$valid, $indexed];
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     */
    private function validateDependencies(string $title, mixed $dependencies, bool $required, array &$decisionGaps): bool
    {
        if (! $required) {
            return true;
        }
        if (! is_array($dependencies) || $dependencies === []) {
            $decisionGaps[] = $this->issue('missing_continuity_dependencies', "{$title} has no explicit service and recovery dependency map.");

            return false;
        }
        foreach ($dependencies as $dependency) {
            if (! $this->completeRecord($dependency, ['key', 'name', 'type', 'owner', 'failure_impact', 'recovery_role'])) {
                $decisionGaps[] = $this->issue('incomplete_continuity_dependency', "{$title} has an incomplete dependency record.");

                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     */
    private function validatePlan(string $title, mixed $plan, bool $required, array &$decisionGaps): bool
    {
        if (! $required) {
            return true;
        }
        $valid = $this->completeRecord($plan, ['coordinator_key', 'coordinator', 'scenario', 'participants', 'success_criteria', 'communications', 'safe_execution_boundary'])
            && is_array($plan['safe_execution_boundary'] ?? null)
            && ($plan['safe_execution_boundary']['production_changes_prohibited'] ?? false) === true
            && ($plan['safe_execution_boundary']['test_data_disposition'] ?? '') !== '';
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_safe_exercise_plan', "{$title} lacks a complete plan or safe non-production execution boundary.");
        }

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function validateApproval(string $title, mixed $approval, bool $required, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $required) {
            return true;
        }
        $valid = $this->completeRecord($approval, ['outcome', 'approved_by', 'authority_basis', 'scope_digest', 'risk_accepted', 'approved_at', 'evidence_record_key'])
            && ($approval['outcome'] ?? null) === 'approved';
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_continuity_approval', "{$title} lacks explicit approval of its exact scope, objectives, risk, and plan.");
        }
        $this->requireEvidence(is_array($approval) ? ($approval['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} approval", $evidenceGaps);

        return $valid;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function validateBackupBaseline(string $title, mixed $backup, bool $required, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $required) {
            return true;
        }
        $valid = $this->completeRecord($backup, ['backup_set', 'owner', 'scope', 'storage_boundary', 'encrypted', 'retention', 'last_successful_at', 'recovery_point_at', 'integrity_check', 'evidence_record_key'])
            && ($backup['encrypted'] ?? false) === true;
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_backup_baseline', "{$title} lacks an authorized, encrypted, attributable backup and recovery-point baseline.");
        }
        $this->requireEvidence(is_array($backup) ? ($backup['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} backup baseline", $evidenceGaps);

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $decisionGaps
     *
     * @param-out list<array{code: string, message: string}> $decisionGaps
     *
     * @return array{string, bool}
     */
    private function validateSchedule(string $title, mixed $schedule, bool $required, Carbon $asOf, array &$decisionGaps): array
    {
        if (! $required) {
            return ['undefined', true];
        }
        if (! $this->completeRecord($schedule, ['starts_at', 'ends_at', 'timezone'])) {
            $decisionGaps[] = $this->issue('missing_continuity_schedule', "{$title} has no bounded exercise schedule.");

            return ['undefined', false];
        }
        $starts = $this->date($schedule['starts_at']);
        $ends = $this->date($schedule['ends_at']);
        if ($starts === null || $ends === null || $ends->lessThanOrEqualTo($starts)) {
            $decisionGaps[] = $this->issue('invalid_continuity_schedule', "{$title} has an invalid exercise schedule.");

            return ['undefined', false];
        }

        return [match (true) {
            $asOf->lessThan($starts) => 'before_window',
            $asOf->greaterThan($ends) => 'after_window',
            default => 'within_window',
        }, true];
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
     * @return array{bool, string, ?Carbon}
     */
    private function validateExecution(string $title, mixed $execution, bool $required, bool $completeRequired, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! $required) {
            return [true, '', null];
        }
        $baseValid = $this->completeRecord($execution, ['coordinator_key', 'coordinator', 'started_at', 'timeline', 'evidence_record_key']);
        $completedAt = $this->date(is_array($execution) ? ($execution['completed_at'] ?? null) : null);
        $valid = $baseValid && (! $completeRequired || $completedAt !== null);
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_continuity_execution', "{$title} lacks a complete attributable execution timeline.");
        }
        $this->requireEvidence(is_array($execution) ? ($execution['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} execution", $evidenceGaps);
        if ($baseValid && $completeRequired && $this->date($execution['started_at'])?->greaterThanOrEqualTo($completedAt) === true) {
            $conflicts[] = $this->issue('invalid_continuity_execution_sequence', "{$title} completion does not follow exercise start.");
            $valid = false;
        }

        return [$valid, is_array($execution) ? (string) ($execution['coordinator_key'] ?? '') : '', $completedAt];
    }

    /**
     * @param  array<string, array<string, mixed>>  $objectives
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return array{bool, array<string, array<string, mixed>>, bool}
     */
    private function validateRestoreResult(string $title, mixed $result, bool $required, array $objectives, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! $required) {
            return [true, [], true];
        }
        $valid = $this->completeRecord($result, ['target', 'isolated_environment', 'recovery_point_used_at', 'integrity_result', 'security_result', 'observations', 'test_data_disposition', 'evidence_record_key'])
            && ($result['isolated_environment'] ?? false) === true;
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_restore_result', "{$title} lacks an isolated restore or failover result; backup status alone is insufficient.");
        }
        $this->requireEvidence(is_array($result) ? ($result['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} restore result", $evidenceGaps);
        $observations = [];
        if (is_array($result['observations'] ?? null)) {
            foreach ($result['observations'] as $observation) {
                $objectiveKey = is_array($observation) ? (string) ($observation['objective_key'] ?? '') : '';
                $complete = $this->completeRecord($observation, ['objective_key', 'observed_recovery_time_seconds', 'observed_recovery_point_age_seconds', 'observed_result', 'evidence_record_key']);
                $numeric = is_int($observation['observed_recovery_time_seconds'] ?? null) && $observation['observed_recovery_time_seconds'] >= 0
                    && is_int($observation['observed_recovery_point_age_seconds'] ?? null) && $observation['observed_recovery_point_age_seconds'] >= 0;
                if (! $complete || ! $numeric || ! isset($objectives[$objectiveKey]) || isset($observations[$objectiveKey])) {
                    $conflicts[] = $this->issue('invalid_recovery_observation', "{$title} has an invalid, duplicate, or unbound recovery observation.");
                    $valid = false;

                    continue;
                }
                $this->requireEvidence($observation['evidence_record_key'], $evidenceKeys, "{$title} recovery observation {$objectiveKey}", $evidenceGaps);
                $observations[$objectiveKey] = $observation;
            }
        }
        if (count($observations) !== count($objectives)) {
            $decisionGaps[] = $this->issue('unmeasured_recovery_objective', "{$title} did not record observed recovery time and recovery-point age for every approved objective.");
            $valid = false;
        }
        $cleanupValid = is_array($result) && in_array($result['test_data_disposition'] ?? null, ['disposed', 'retained_under_authority'], true);
        if (! $cleanupValid) {
            $decisionGaps[] = $this->issue('uncontrolled_restore_data', "{$title} has no verified disposition for restored test data.");
        }

        return [$valid, $observations, $cleanupValid];
    }

    /**
     * @param  array<string, array<string, mixed>>  $correctiveActions
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $decisionGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return array{bool, int, ?Carbon}
     */
    private function validateVerification(string $title, mixed $verification, bool $required, string $coordinatorKey, ?Carbon $executionCompletedAt, int $missedObjectives, array $correctiveActions, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        if (! $required) {
            return [true, 0, null];
        }
        $valid = $this->completeRecord($verification, ['verified_by_key', 'verified_by', 'standard', 'outcome', 'summary', 'no_material_gaps', 'gaps', 'verified_at', 'evidence_record_key'])
            && in_array($verification['outcome'] ?? null, ['passed', 'partial', 'failed'], true);
        $verifiedAt = $this->date(is_array($verification) ? ($verification['verified_at'] ?? null) : null);
        if (! $valid) {
            $decisionGaps[] = $this->issue('missing_continuity_verification', "{$title} lacks an independent verification of results and gaps.");
        }
        if ($valid && ($verification['verified_by_key'] ?? null) === $coordinatorKey) {
            $conflicts[] = $this->issue('self_verified_continuity_exercise', "{$title} is verified by its exercise coordinator.");
            $valid = false;
        }
        if ($valid && ($executionCompletedAt === null || $verifiedAt === null || $verifiedAt->lessThanOrEqualTo($executionCompletedAt))) {
            $conflicts[] = $this->issue('invalid_continuity_verification_sequence', "{$title} verification must follow completed execution.");
            $valid = false;
        }
        $this->requireEvidence(is_array($verification) ? ($verification['evidence_record_key'] ?? null) : null, $evidenceKeys, "{$title} verification", $evidenceGaps);
        $gaps = is_array($verification['gaps'] ?? null) ? $verification['gaps'] : [];
        if ($gaps === [] && ($verification['no_material_gaps'] ?? null) !== true) {
            $decisionGaps[] = $this->issue('missing_continuity_gap_disposition', "{$title} neither records material gaps nor explicitly verifies that none were found.");
            $valid = false;
        }
        if ($gaps !== [] && ($verification['no_material_gaps'] ?? false) === true) {
            $conflicts[] = $this->issue('contradictory_continuity_gap_assessment', "{$title} claims no material gaps while recording gaps.");
            $valid = false;
        }
        if ($missedObjectives > 0 && $gaps === []) {
            $conflicts[] = $this->issue('missed_objective_without_gap', "{$title} missed an approved recovery objective but records no material gap.");
            $valid = false;
        }
        $unresolved = 0;
        foreach ($gaps as $gap) {
            if (! $this->completeRecord($gap, ['key', 'finding', 'impact', 'corrective_action_key']) || ! isset($correctiveActions[$gap['corrective_action_key']])) {
                $decisionGaps[] = $this->issue('unaccountable_continuity_gap', "{$title} has a material gap without a linked canonical Corrective Action.");
                $unresolved++;
            }
        }

        return [$valid, $unresolved, $verifiedAt];
    }

    /**
     * @param  array<string, array<string, mixed>>  $objectives
     * @param  array<string, array<string, mixed>>  $observations
     * @return array{met: int, missed: int, not_measured: int}
     */
    private function objectiveSummary(array $objectives, array $observations): array
    {
        $met = 0;
        $missed = 0;
        foreach ($objectives as $key => $objective) {
            $observation = $observations[$key] ?? null;
            if ($observation === null) {
                continue;
            }
            if ($observation['observed_recovery_time_seconds'] <= $objective['rto_seconds'] && $observation['observed_recovery_point_age_seconds'] <= $objective['rpo_seconds']) {
                $met++;
            } else {
                $missed++;
            }
        }

        return ['met' => $met, 'missed' => $missed, 'not_measured' => count($objectives) - count($observations)];
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $readinessGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $readinessGaps
     *
     * @return array{list<array<string, mixed>>, bool}
     */
    private function resolvePolicies(ContinuityExerciseDefinition $definition, ResolvedPolicyRegistry $registry, Carbon $asOf, array &$conflicts, array &$readinessGaps): array
    {
        $resolved = [];
        $allOperative = true;
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
                $conflicts[] = $this->issue('missing_continuity_policy', "Continuity Exercises reference missing policy {$reference['key']} version {$reference['version']}.");
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
            if ($reference['required_for_approval'] && ! $operative) {
                $allOperative = false;
                $readinessGaps[] = $this->issue('continuity_policy_not_operative', "{$reference['key']} version {$reference['version']} is not operative for Continuity Exercise approval.");
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
                $conflicts[] = $this->issue('invalid_continuity_evidence_key', 'A Continuity Evidence Record has a missing or duplicate key.');
            }
            $complete = $this->completeRecord($record, ['key', 'record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state']);
            if (! $complete || ($record['state'] ?? null) !== 'final' || $this->date($record['recorded_at'] ?? null) === null) {
                $evidenceGaps[] = $this->issue('incomplete_continuity_evidence', "Evidence {$key} is incomplete, invalid, or not final.");
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
                $conflicts[] = $this->issue('invalid_continuity_requirement', 'A Continuity Exercise requirement is missing, incomplete, or duplicated.');
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
            $evidenceGaps[] = $this->issue('missing_continuity_evidence', "{$subject} does not reference a complete Evidence Record.");
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

    private function valuesWithin(mixed $values, mixed $allowed): bool
    {
        if (! is_array($values) || ! is_array($allowed)) {
            return false;
        }
        foreach ($values as $value) {
            if (! is_string($value) || ! in_array($value, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    private function operationalStatus(?ContinuityExerciseLifecycleStatus $status, bool $mayExecute, bool $verificationValid, bool $closureValid, int $unresolvedGaps): string
    {
        return match (true) {
            $status === ContinuityExerciseLifecycleStatus::Closed && $closureValid => 'closed_verified',
            $status === ContinuityExerciseLifecycleStatus::Closed => 'blocked_closure',
            $status === ContinuityExerciseLifecycleStatus::Verified && $verificationValid && $unresolvedGaps === 0 => 'ready_for_closure',
            $status === ContinuityExerciseLifecycleStatus::Verified => 'verified_with_open_gaps',
            $status === ContinuityExerciseLifecycleStatus::AwaitingVerification => 'awaiting_verification',
            $status === ContinuityExerciseLifecycleStatus::InProgress => 'exercise_in_progress',
            $mayExecute => 'authorized_for_execution',
            $status === ContinuityExerciseLifecycleStatus::Scheduled => 'blocked_scheduled_exercise',
            $status === ContinuityExerciseLifecycleStatus::Approved => 'approved_not_scheduled',
            $status === ContinuityExerciseLifecycleStatus::Cancelled => 'cancelled',
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
