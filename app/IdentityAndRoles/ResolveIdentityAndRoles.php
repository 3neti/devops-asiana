<?php

namespace App\IdentityAndRoles;

use App\FormationCompletion\ResolvedFormationCompletion;
use App\Partnership\ResolvedPartnership;
use App\ResponsibilityCoverage\ResolvedResponsibilityCoverage;
use App\RoleActivations\ResolvedRoleActivations;
use App\RoleTransitions\ResolvedRoleTransitions;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveIdentityAndRoles
{
    public function handle(
        IdentityAndRoleDefinition $definition,
        ResolvedPartnership $partnership,
        ResolvedResponsibilityCoverage $responsibilityCoverage,
        ?DateTimeImmutable $asOf = null,
        ?ResolvedFormationCompletion $formationCompletion = null,
        ?ResolvedRoleActivations $roleActivations = null,
        ?ResolvedRoleTransitions $roleTransitions = null,
    ): ResolvedIdentityAndRoles {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $identityGaps */
        $identityGaps = [];
        /** @var list<array{code: string, message: string}> $activationGaps */
        $activationGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $holderMismatches */
        $holderMismatches = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $assignmentLifecycleCounts = array_fill_keys(
            array_map(static fn (AssignmentLifecycleStatus $status): string => $status->value, AssignmentLifecycleStatus::cases()),
            0,
        );
        $roleCoverageCounts = array_fill_keys(['covered', 'vacant', 'pending_activation', 'conflicted'], 0);
        $partners = $this->indexByKey($partnership->formation['founding_partners'] ?? []);
        $coverageRequirements = $this->indexByKey($responsibilityCoverage->requirements);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        $identities = $this->resolveIdentities($definition->identities, $partners, $conflicts, $identityGaps);
        $identityIndex = $this->indexByKey($identities);
        $roles = $this->validateRoles($definition->roles, $coverageRequirements, $conflicts);
        $roleIndex = $this->indexByKey($roles);
        $commencementBasis = collect($formationCompletion->officeActivationBases ?? [])
            ->firstWhere('permits_formation_derived_assignments', true);
        $firmEffectiveAt = $this->date($commencementBasis['effective_at'] ?? null);
        $activationAdmissions = $this->indexByAssignmentKey($roleActivations->assignmentActivationAdmissions ?? []);
        $transitionAdmissions = $this->indexByAssignmentKey($roleTransitions->assignmentTransitionAdmissions ?? []);

        if ($firmEffectiveAt === null && $this->usesFormationEffectiveDate($definition->assignments)) {
            $activationGaps[] = $this->issue(
                'formation_commencement_unverified',
                'Formation-derived assignments cannot activate until a verified Firm Commencement basis is effective.',
            );
        }

        $assignments = $this->resolveAssignments(
            assignments: $definition->assignments,
            identities: $identityIndex,
            roles: $roleIndex,
            evidenceKeys: $evidenceKeys,
            firmEffectiveAt: $firmEffectiveAt,
            activationAdmissions: $activationAdmissions,
            transitionAdmissions: $transitionAdmissions,
            asOf: $effectiveAt,
            lifecycleCounts: $assignmentLifecycleCounts,
            conflicts: $conflicts,
            activationGaps: $activationGaps,
            evidenceGaps: $evidenceGaps,
        );
        $assignmentsByRole = $this->groupAssignmentsByRole($assignments);
        $resolvedRoles = $this->resolveRoleCoverage(
            $roles,
            $assignmentsByRole,
            $coverageRequirements,
            $roleCoverageCounts,
            $holderMismatches,
        );

        return new ResolvedIdentityAndRoles(
            schemaVersion: $definition->schemaVersion,
            identities: $identities,
            roles: $resolvedRoles,
            assignments: $assignments,
            evidenceRecords: $definition->evidenceRecords,
            assignmentLifecycleCounts: $assignmentLifecycleCounts,
            roleCoverageCounts: $roleCoverageCounts,
            conflicts: $conflicts,
            identityGaps: $identityGaps,
            activationGaps: $activationGaps,
            evidenceGaps: $evidenceGaps,
            holderMismatches: $holderMismatches,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $partners
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $identityGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $identityGaps
     *
     * @return list<array<string, mixed>>
     */
    private function resolveIdentities(array $records, array $partners, array &$conflicts, array &$identityGaps): array
    {
        $resolved = [];
        $identityKeys = [];
        $partnershipPersonKeys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $partnershipPersonKey = (string) ($record['partnership_person_key'] ?? '');
            $partner = $partners[$partnershipPersonKey] ?? null;
            if ($key === '' || in_array($key, $identityKeys, true)) {
                $conflicts[] = $this->issue('invalid_identity_key', 'An Institutional Identity has a missing or duplicate key.');
            }
            $identityKeys[] = $key;
            if ($partnershipPersonKey === '' || in_array($partnershipPersonKey, $partnershipPersonKeys, true)) {
                $conflicts[] = $this->issue('duplicate_partnership_identity', "Identity {$key} has a missing or duplicated Partnership person reference.");
            }
            $partnershipPersonKeys[] = $partnershipPersonKey;
            if ($partner === null) {
                $conflicts[] = $this->issue('unknown_partnership_identity', "Identity {$key} refers to an unknown Partnership person.");
            }
            if (($record['subject_type'] ?? null) !== 'person' || ! in_array($record['lifecycle_status'] ?? null, ['recognized', 'inactive', 'archived'], true)) {
                $conflicts[] = $this->issue('invalid_identity_state', "Identity {$key} has an invalid subject type or lifecycle status.");
            }
            if (($record['employment_relationship']['state'] ?? null) === 'unresolved') {
                $identityGaps[] = $this->issue(
                    'employment_relationship_unresolved',
                    ($partner['name'] ?? $key).' has no settled employment, partnership, or service classification in this registry.',
                );
            }

            $resolved[] = [
                ...$record,
                'display_name' => $partner['name'] ?? $key,
                'partner_status' => $partner['partner_status'] ?? null,
                'authentication_bound' => ($record['authentication_binding'] ?? null) !== null,
                'institutional_status' => $partner === null ? 'conflicted' : ($record['lifecycle_status'] ?? 'invalid'),
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return list<array<string, mixed>>
     */
    private function validateRoles(array $records, array $requirements, array &$conflicts): array
    {
        $keys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $category = $record['category'] ?? null;
            $attachment = $record['authority_attachment'] ?? null;
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_role_key', 'An Institutional Role has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (! isset($requirements[$record['responsibility_requirement_key'] ?? ''])) {
                $conflicts[] = $this->issue('unknown_role_requirement', "Role {$key} refers to an unknown Responsibility Coverage requirement.");
            }
            $attachmentValid = match ($category) {
                'office' => $attachment === 'office',
                'professional_responsibility' => in_array($attachment, ['professional_role', 'none'], true),
                'delegated_authority' => $attachment === 'delegation',
                default => false,
            };
            if (! $attachmentValid) {
                $conflicts[] = $this->issue('invalid_role_authority_attachment', "Role {$key} has an authority attachment inconsistent with its category.");
            }
        }

        return $records;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<string, array<string, mixed>>  $identities
     * @param  array<string, array<string, mixed>>  $roles
     * @param  list<string>  $evidenceKeys
     * @param  array<string, array<string, mixed>>  $activationAdmissions
     * @param  array<string, array<string, mixed>>  $transitionAdmissions
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $activationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out array<string, int> $lifecycleCounts
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $activationGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return list<array<string, mixed>>
     */
    private function resolveAssignments(
        array $assignments,
        array $identities,
        array $roles,
        array $evidenceKeys,
        ?Carbon $firmEffectiveAt,
        array $activationAdmissions,
        array $transitionAdmissions,
        Carbon $asOf,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$activationGaps,
        array &$evidenceGaps,
    ): array {
        $resolved = [];
        $assignmentKeys = [];
        $currentRoleIdentityPairs = [];

        foreach ($assignments as $assignment) {
            $key = (string) ($assignment['key'] ?? '');
            $roleKey = (string) ($assignment['role_key'] ?? '');
            $identityKey = (string) ($assignment['identity_key'] ?? '');
            $status = AssignmentLifecycleStatus::tryFrom((string) ($assignment['lifecycle_status'] ?? ''));
            $role = $roles[$roleKey] ?? null;
            $identity = $identities[$identityKey] ?? null;
            if ($key === '' || in_array($key, $assignmentKeys, true)) {
                $conflicts[] = $this->issue('invalid_assignment_key', 'A Role Assignment has a missing or duplicate key.');
            }
            $assignmentKeys[] = $key;
            if ($role === null) {
                $conflicts[] = $this->issue('unknown_assignment_role', "Assignment {$key} refers to unknown role {$roleKey}.");
            }
            if ($identity === null) {
                $conflicts[] = $this->issue('unknown_assignment_identity', "Assignment {$key} refers to unknown identity {$identityKey}.");
            }
            if ($status === null) {
                $conflicts[] = $this->issue('invalid_assignment_lifecycle', "Assignment {$key} has an invalid lifecycle status.");
            } else {
                $lifecycleCounts[$status->value]++;
            }

            $isCurrentRecord = $status !== null && ! in_array($status, [AssignmentLifecycleStatus::Ended, AssignmentLifecycleStatus::Revoked], true);
            $pair = "{$roleKey}:{$identityKey}";
            if ($isCurrentRecord && in_array($pair, $currentRoleIdentityPairs, true)) {
                $conflicts[] = $this->issue('duplicate_current_role_assignment', "Role {$roleKey} has duplicate current assignments for {$identityKey}.");
            }
            if ($isCurrentRecord) {
                $currentRoleIdentityPairs[] = $pair;
            }

            $basis = is_array($assignment['basis'] ?? null) ? $assignment['basis'] : [];
            $basisType = $basis['type'] ?? null;
            $activationAdmission = $activationAdmissions[$key] ?? null;
            $transitionAdmission = $transitionAdmissions[$key] ?? null;
            $basisValid = in_array($basisType, ['formation', 'appointment', 'delegation'], true)
                && is_string($basis['reference'] ?? null) && $basis['reference'] !== '';
            if (! $basisValid) {
                $conflicts[] = $this->issue('invalid_assignment_basis', "Assignment {$key} lacks an explicit institutional basis.");
            }
            $qualified = $role !== null && $identity !== null
                && in_array($identity['partner_status'] ?? null, $role['qualified_partner_statuses'] ?? [], true);
            if (! $qualified) {
                $conflicts[] = $this->issue('unqualified_role_assignment', "Assignment {$key} does not satisfy the Role qualification.");
            }

            $effectiveFrom = $this->resolveEffectiveFrom($assignment, $firmEffectiveAt, $activationAdmission);
            $expiresAt = $this->date($assignment['expires_at'] ?? null);
            if ($effectiveFrom !== null && $expiresAt !== null && $expiresAt->lessThanOrEqualTo($effectiveFrom)) {
                $conflicts[] = $this->issue('invalid_assignment_period', "Assignment {$key} expires before or at its effective time.");
            }
            $approvalValid = $basisType === 'formation' || $this->completeRecord(
                $assignment['approval'] ?? null,
                ['approver', 'authority_basis', 'outcome', 'decided_at', 'evidence_record_key'],
            );
            if ($basisType !== 'formation' && $status !== AssignmentLifecycleStatus::Proposed && ! $approvalValid) {
                $activationGaps[] = $this->issue('missing_assignment_approval', "Assignment {$key} lacks approval separate from assignment execution.");
            }

            $delegationValid = $role === null || ($role['category'] ?? null) !== 'delegated_authority'
                || ($basisType === 'delegation'
                    && $this->completeRecord($assignment['authority_scope'] ?? null, ['domains', 'actions', 'limits', 'subdelegation_allowed'])
                    && $expiresAt !== null);
            if (! $delegationValid && $status !== AssignmentLifecycleStatus::Proposed) {
                $activationGaps[] = $this->issue('incomplete_delegated_authority', "Assignment {$key} cannot carry delegated authority without bounded scope and expiry.");
            }

            $effectiveStatus = $status;
            if ($basisType === 'formation' && $activationAdmission !== null && $status === AssignmentLifecycleStatus::Approved) {
                $effectiveStatus = AssignmentLifecycleStatus::Active;
            }
            if ($transitionAdmission !== null) {
                $effectiveStatus = AssignmentLifecycleStatus::tryFrom((string) ($transitionAdmission['effective_lifecycle_status'] ?? '')) ?? $effectiveStatus;
            }
            if ($basisType === 'formation' && $status === AssignmentLifecycleStatus::Active && $activationAdmission === null) {
                $activationGaps[] = $this->issue(
                    'formation_assignment_activation_not_admitted',
                    "Assignment {$key} declares Active without an admitted holder-assumption record.",
                );
            }

            $evidenceValid = $basisType === 'formation' ? $activationAdmission !== null : true;
            if ($basisType !== 'formation' && $status === AssignmentLifecycleStatus::Active) {
                $evidenceValid = $this->requireEvidence($assignment['evidence_record_key'] ?? null, $evidenceKeys, "Assignment {$key}", $evidenceGaps);
            }
            if (in_array($status, [AssignmentLifecycleStatus::Ended, AssignmentLifecycleStatus::Revoked], true)) {
                $disposition = $assignment['disposition'] ?? null;
                if (! $this->completeRecord($disposition, ['reason', 'decided_by', 'decided_at', 'evidence_record_key'])) {
                    $conflicts[] = $this->issue('missing_assignment_disposition', "Assignment {$key} ended without an attributable disposition.");
                } else {
                    $this->requireEvidence($disposition['evidence_record_key'], $evidenceKeys, "Assignment {$key} disposition", $evidenceGaps);
                }
            }

            $temporallyActive = $effectiveFrom !== null && ! $effectiveFrom->isAfter($asOf)
                && ($expiresAt === null || $expiresAt->isAfter($asOf));
            $operative = $effectiveStatus === AssignmentLifecycleStatus::Active && $temporallyActive
                && $basisValid && $qualified && $approvalValid && $delegationValid && $evidenceValid;
            $grantsFirmAuthority = $operative && in_array($role['category'] ?? null, ['office', 'delegated_authority'], true);

            $resolved[] = [
                ...$assignment,
                'lifecycle_status_label' => $status?->label() ?? 'Invalid',
                'effective_lifecycle_status' => $effectiveStatus?->value,
                'effective_lifecycle_status_label' => $effectiveStatus?->label() ?? 'Invalid',
                'identity_name' => $identity['display_name'] ?? $identityKey,
                'role_name' => $role['name'] ?? $roleKey,
                'activation_admitted' => $activationAdmission !== null,
                'activation_admission_key' => $activationAdmission['key'] ?? null,
                'transition_admitted' => $transitionAdmission !== null,
                'transition_admission_key' => $transitionAdmission['key'] ?? null,
                'effective_at_resolved' => $effectiveFrom?->toIso8601String(),
                'temporal_state' => $this->temporalState($effectiveFrom, $expiresAt, $asOf),
                'operative' => $operative,
                'grants_firm_authority' => $grantsFirmAuthority,
                'operational_status' => $this->operationalStatus($effectiveStatus, $effectiveFrom, $expiresAt, $asOf, $operative),
            ];
        }

        $this->validateExclusiveRoles($resolved, $roles, $conflicts);

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $roles
     * @param  array<string, list<array<string, mixed>>>  $assignmentsByRole
     * @param  array<string, array<string, mixed>>  $coverageRequirements
     * @param  array<string, int>  $coverageCounts
     * @param  list<array{code: string, message: string}>  $holderMismatches
     *
     * @param-out array<string, int> $coverageCounts
     * @param-out list<array{code: string, message: string}> $holderMismatches
     *
     * @return list<array<string, mixed>>
     */
    private function resolveRoleCoverage(
        array $roles,
        array $assignmentsByRole,
        array $coverageRequirements,
        array &$coverageCounts,
        array &$holderMismatches,
    ): array {
        $resolved = [];

        foreach ($roles as $role) {
            $assignments = $assignmentsByRole[$role['key']] ?? [];
            $rawCurrentAssignments = array_values(array_filter(
                $assignments,
                static fn (array $assignment): bool => ! in_array($assignment['lifecycle_status'] ?? null, ['ended', 'revoked'], true),
            ));
            $currentAssignments = array_values(array_filter(
                $rawCurrentAssignments,
                static fn (array $assignment): bool => ! in_array($assignment['effective_lifecycle_status'] ?? $assignment['lifecycle_status'] ?? null, ['ended', 'revoked'], true),
            ));
            $recordedHolderKeys = array_values(array_unique(array_column($currentAssignments, 'identity_key')));
            $rawHolderKeys = array_values(array_unique(array_column($rawCurrentAssignments, 'identity_key')));
            $requirement = $coverageRequirements[$role['responsibility_requirement_key']] ?? null;
            $expectedHolderKeys = array_values($requirement['holder_keys'] ?? []);
            $transitionedOut = $currentAssignments === [] && $rawHolderKeys === $expectedHolderKeys && $rawCurrentAssignments !== [];
            $matchesCoverage = $recordedHolderKeys === $expectedHolderKeys || $transitionedOut;
            if (! $matchesCoverage) {
                $holderMismatches[] = $this->issue(
                    'role_holder_mismatch',
                    "Role {$role['name']} records holders differently from Responsibility Coverage.",
                );
            }
            $operativeAssignments = array_filter(
                $currentAssignments,
                static fn (array $assignment): bool => ($assignment['operative'] ?? false) === true,
            );
            $coverageStatus = match (true) {
                ! $matchesCoverage => 'conflicted',
                ($requirement['coverage_status'] ?? null) === 'vacant' || $transitionedOut => 'vacant',
                $operativeAssignments !== [] => 'covered',
                $currentAssignments !== [] => 'pending_activation',
                default => 'vacant',
            };
            $coverageCounts[$coverageStatus]++;
            $resolved[] = [
                ...$role,
                'expected_holder_keys' => $expectedHolderKeys,
                'recorded_holder_keys' => $recordedHolderKeys,
                'recorded_holder_names' => array_column($currentAssignments, 'identity_name'),
                'transitioned_out' => $transitionedOut,
                'coverage_status' => $coverageStatus,
                'operative_assignment_count' => count($operativeAssignments),
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<string, array<string, mixed>>  $roles
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     */
    private function validateExclusiveRoles(array $assignments, array $roles, array &$conflicts): void
    {
        foreach ($roles as $roleKey => $role) {
            if (($role['exclusive'] ?? false) !== true) {
                continue;
            }
            $current = array_filter(
                $assignments,
                static fn (array $assignment): bool => ($assignment['role_key'] ?? null) === $roleKey
                    && ! in_array($assignment['effective_lifecycle_status'] ?? $assignment['lifecycle_status'] ?? null, ['ended', 'revoked'], true),
            );
            if (count($current) > 1) {
                $conflicts[] = $this->issue('exclusive_role_overlap', "Exclusive role {$role['name']} has multiple current assignments.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $assignment
     * @param  array<string, mixed>|null  $activationAdmission
     */
    private function resolveEffectiveFrom(array $assignment, ?Carbon $firmEffectiveAt, ?array $activationAdmission): ?Carbon
    {
        if (($assignment['effective_at_source'] ?? null) === 'formation.firm.effective_date') {
            return $this->date($activationAdmission['effective_at'] ?? null) ?? $firmEffectiveAt;
        }

        return $this->date($assignment['effective_at'] ?? null);
    }

    private function temporalState(?Carbon $effectiveFrom, ?Carbon $expiresAt, Carbon $asOf): string
    {
        return match (true) {
            $effectiveFrom === null => 'effective_time_unresolved',
            $effectiveFrom->isAfter($asOf) => 'before_effective_time',
            $expiresAt !== null && ! $expiresAt->isAfter($asOf) => 'expired',
            default => 'within_effective_period',
        };
    }

    private function operationalStatus(?AssignmentLifecycleStatus $status, ?Carbon $effectiveFrom, ?Carbon $expiresAt, Carbon $asOf, bool $operative): string
    {
        return match (true) {
            $status === null => 'invalid',
            $status === AssignmentLifecycleStatus::Proposed => 'proposed',
            $status === AssignmentLifecycleStatus::Approved && $effectiveFrom === null => 'approved_pending_effective_time',
            $status === AssignmentLifecycleStatus::Approved => 'approved_ready_for_activation',
            $status === AssignmentLifecycleStatus::Active && $operative => 'active',
            $status === AssignmentLifecycleStatus::Active && $expiresAt !== null && ! $expiresAt->isAfter($asOf) => 'expired_not_ended',
            $status === AssignmentLifecycleStatus::Active => 'blocked_active_assignment',
            $status === AssignmentLifecycleStatus::Suspended => 'suspended',
            $status === AssignmentLifecycleStatus::Ended => 'ended',
            $status === AssignmentLifecycleStatus::Revoked => 'revoked',
        };
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupAssignmentsByRole(array $records): array
    {
        $grouped = [];

        foreach ($records as $record) {
            if (is_string($record['role_key'] ?? null)) {
                $grouped[$record['role_key']][] = $record;
            }
        }

        return $grouped;
    }

    /** @param list<array<string, mixed>> $assignments */
    private function usesFormationEffectiveDate(array $assignments): bool
    {
        foreach ($assignments as $assignment) {
            if (($assignment['effective_at_source'] ?? null) === 'formation.firm.effective_date') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return list<string>
     */
    private function validateEvidence(array $records, array &$conflicts, array &$evidenceGaps): array
    {
        $keys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_identity_role_evidence_key', 'An Evidence Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (! $this->completeRecord($record, ['record_type', 'actor', 'occurred_at', 'source', 'reason', 'approval', 'state', 'supporting_evidence'])) {
                $evidenceGaps[] = $this->issue('incomplete_identity_role_evidence', "Evidence Record {$key} is incomplete.");
            }
        }

        return $keys;
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     */
    private function requireEvidence(mixed $evidenceKey, array $evidenceKeys, string $subject, array &$evidenceGaps): bool
    {
        $valid = is_string($evidenceKey) && in_array($evidenceKey, $evidenceKeys, true);
        if (! $valid) {
            $evidenceGaps[] = $this->issue('missing_identity_role_evidence', "{$subject} lacks a complete linked Evidence Record.");
        }

        return $valid;
    }

    /** @param list<string> $fields */
    private function completeRecord(mixed $record, array $fields): bool
    {
        if (! is_array($record)) {
            return false;
        }

        foreach ($fields as $field) {
            if (! array_key_exists($field, $record) || $record[$field] === null || $record[$field] === '' || $record[$field] === []) {
                return false;
            }
        }

        return true;
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
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $indexed = [];

        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $indexed[$record['key']] = $record;
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByAssignmentKey(array $records): array
    {
        $indexed = [];

        foreach ($records as $record) {
            if ((($record['activates_exact_assignment'] ?? false) === true || ($record['source_type'] ?? null) === 'role_transition')
                && is_string($record['assignment_key'] ?? null)) {
                $indexed[$record['assignment_key']] = $record;
            }
        }

        return $indexed;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
