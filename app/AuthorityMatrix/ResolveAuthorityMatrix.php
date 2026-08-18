<?php

namespace App\AuthorityMatrix;

use App\IdentityAndRoles\ResolvedIdentityAndRoles;
use App\Partnership\ResolvedPartnership;
use App\Policies\ResolvedPolicyRegistry;
use App\ResponsibilityCoverage\ResolvedResponsibilityCoverage;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveAuthorityMatrix
{
    public function handle(
        AuthorityMatrixDefinition $definition,
        ResolvedPartnership $partnership,
        ResolvedPolicyRegistry $policies,
        ResolvedResponsibilityCoverage $responsibilityCoverage,
        ResolvedIdentityAndRoles $identityAndRoles,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedAuthorityMatrix {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $sourceGaps */
        $sourceGaps = [];
        /** @var list<array{code: string, message: string}> $holderGaps */
        $holderGaps = [];
        /** @var list<array{code: string, message: string}> $boundaryGaps */
        $boundaryGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $lifecycleCounts = array_fill_keys(
            array_map(static fn (AuthorityEntryLifecycleStatus $status): string => $status->value, AuthorityEntryLifecycleStatus::cases()),
            0,
        );
        $resolutionCounts = array_fill_keys(['effective', 'design_only', 'vacant_holder', 'pending_activation', 'blocked', 'conflicted'], 0);
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $policyIndex = $this->indexByKey($policies->policies);
        $requirementIndex = $this->indexByKey($responsibilityCoverage->requirements);
        $identityIndex = $this->indexByKey($identityAndRoles->identities);
        $roleIndex = $this->indexByKey($identityAndRoles->roles);
        $actionIndex = $this->validateDomains($definition->domains, $conflicts);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        $governingPolicy = $this->resolveGoverningPolicy(
            $definition->governingPolicy,
            $policyIndex,
            $effectiveAt,
            $conflicts,
            $sourceGaps,
        );
        $firmEffectiveAt = $this->date($partnership->formation['firm']['effective_date'] ?? null);
        $resolvedEntries = [];
        $entryKeys = [];

        foreach ($definition->entries as $entry) {
            $resolvedEntries[] = $this->resolveEntry(
                entry: $entry,
                actionIndex: $actionIndex,
                requirementIndex: $requirementIndex,
                policyIndex: $policyIndex,
                identityIndex: $identityIndex,
                roleIndex: $roleIndex,
                assignments: $identityAndRoles->assignments,
                governingPolicyOperative: $governingPolicy['operative'] === true,
                firmEffectiveAt: $firmEffectiveAt,
                asOf: $effectiveAt,
                evidenceKeys: $evidenceKeys,
                entryKeys: $entryKeys,
                lifecycleCounts: $lifecycleCounts,
                resolutionCounts: $resolutionCounts,
                conflicts: $conflicts,
                sourceGaps: $sourceGaps,
                holderGaps: $holderGaps,
                boundaryGaps: $boundaryGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedAuthorityMatrix(
            schemaVersion: $definition->schemaVersion,
            governingPolicy: $governingPolicy,
            domains: $definition->domains,
            entries: $resolvedEntries,
            deferredDecisions: $definition->deferredDecisions,
            evidenceRecords: $definition->evidenceRecords,
            lifecycleCounts: $lifecycleCounts,
            resolutionCounts: $resolutionCounts,
            conflicts: $conflicts,
            sourceGaps: $sourceGaps,
            holderGaps: $holderGaps,
            boundaryGaps: $boundaryGaps,
            evidenceGaps: $evidenceGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, array<string, mixed>>  $actionIndex
     * @param  array<string, array<string, mixed>>  $requirementIndex
     * @param  array<string, array<string, mixed>>  $policyIndex
     * @param  array<string, array<string, mixed>>  $identityIndex
     * @param  array<string, array<string, mixed>>  $roleIndex
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $entryKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  array<string, int>  $resolutionCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $sourceGaps
     * @param  list<array{code: string, message: string}>  $holderGaps
     * @param  list<array{code: string, message: string}>  $boundaryGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     *
     * @param-out list<string> $entryKeys
     * @param-out array<string, int> $lifecycleCounts
     * @param-out array<string, int> $resolutionCounts
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $sourceGaps
     * @param-out list<array{code: string, message: string}> $holderGaps
     * @param-out list<array{code: string, message: string}> $boundaryGaps
     * @param-out list<array{code: string, message: string}> $evidenceGaps
     *
     * @return array<string, mixed>
     */
    private function resolveEntry(
        array $entry,
        array $actionIndex,
        array $requirementIndex,
        array $policyIndex,
        array $identityIndex,
        array $roleIndex,
        array $assignments,
        bool $governingPolicyOperative,
        ?Carbon $firmEffectiveAt,
        Carbon $asOf,
        array $evidenceKeys,
        array &$entryKeys,
        array &$lifecycleCounts,
        array &$resolutionCounts,
        array &$conflicts,
        array &$sourceGaps,
        array &$holderGaps,
        array &$boundaryGaps,
        array &$evidenceGaps,
    ): array {
        $conflictCount = count($conflicts);
        $key = (string) ($entry['key'] ?? '');
        $domainKey = (string) ($entry['domain_key'] ?? '');
        $actionKey = (string) ($entry['action_key'] ?? '');
        $action = $actionIndex["{$domainKey}.{$actionKey}"] ?? null;
        $requirementKey = (string) ($entry['responsibility_requirement_key'] ?? '');
        $requirement = $requirementIndex[$requirementKey] ?? null;
        $lifecycle = AuthorityEntryLifecycleStatus::tryFrom((string) ($entry['lifecycle_status'] ?? ''));

        if ($key === '' || in_array($key, $entryKeys, true)) {
            $conflicts[] = $this->issue('invalid_authority_entry_key', 'An Authority Matrix entry has a missing or duplicate key.');
        }
        $entryKeys[] = $key;
        if ($action === null) {
            $conflicts[] = $this->issue('unknown_authority_action', "Authority entry {$key} refers to an unknown domain action.");
        }
        if ($requirement === null) {
            $conflicts[] = $this->issue('unknown_authority_requirement', "Authority entry {$key} refers to unknown requirement {$requirementKey}.");
        }
        if ($lifecycle === null) {
            $conflicts[] = $this->issue('invalid_authority_lifecycle', "Authority entry {$key} has an invalid lifecycle state.");
        } else {
            $lifecycleCounts[$lifecycle->value]++;
        }

        [$sourceValid, $sourceOperative, $sourceLabel, $sourceEffectiveAt] = $this->resolveEntrySource(
            $entry,
            $requirement,
            $policyIndex,
            $asOf,
            $conflicts,
            $sourceGaps,
        );
        [$candidateHolderKeys, $eligibleHolderKeys, $holderRuleValid] = $this->resolveHolders(
            $entry,
            $requirement,
            $identityIndex,
            $roleIndex,
            $assignments,
            $conflicts,
        );
        $candidateHolderNames = array_map(
            static fn (string $holderKey): string => (string) ($identityIndex[$holderKey]['display_name'] ?? $holderKey),
            $candidateHolderKeys,
        );
        $effectiveFrom = $this->resolveEffectiveFrom($entry, $firmEffectiveAt, $sourceEffectiveAt);
        $expiresAt = $this->date($entry['expires_at'] ?? null);
        $temporalValid = $effectiveFrom !== null && ! $effectiveFrom->isAfter($asOf)
            && ($expiresAt === null || $expiresAt->isAfter($asOf));

        if ($effectiveFrom === null && $lifecycle !== AuthorityEntryLifecycleStatus::Design) {
            $sourceGaps[] = $this->issue('authority_effective_time_unresolved', "Authority entry {$key} has no resolved effective time.");
        }
        if ($effectiveFrom !== null && $expiresAt !== null && $expiresAt->lessThanOrEqualTo($effectiveFrom)) {
            $conflicts[] = $this->issue('invalid_authority_period', "Authority entry {$key} expires before or at its effective time.");
        }

        $scopeValid = $this->validateBoundary($entry, $action, $boundaryGaps, $conflicts);
        $evidenceValid = true;
        if ($lifecycle === AuthorityEntryLifecycleStatus::Active) {
            $evidenceValid = $this->requireEvidence($entry['evidence_record_key'] ?? null, $evidenceKeys, "Authority entry {$key}", $evidenceGaps);
        }
        if ($lifecycle === AuthorityEntryLifecycleStatus::Active && $candidateHolderKeys === []) {
            $holderGaps[] = $this->issue('active_authority_without_holder', "Authority entry {$key} is Active without a candidate holder.");
        } elseif ($lifecycle !== AuthorityEntryLifecycleStatus::Design && $candidateHolderKeys === []) {
            $holderGaps[] = $this->issue('authority_holder_vacant', "Authority entry {$key} has no recorded holder.");
        }
        if ($lifecycle === AuthorityEntryLifecycleStatus::Active && $candidateHolderKeys !== [] && $eligibleHolderKeys === []) {
            $holderGaps[] = $this->issue('authority_holder_not_operative', "Authority entry {$key} has no operative holder assignment or status.");
        }

        $entryConflicted = count($conflicts) > $conflictCount;
        $isEffective = $lifecycle === AuthorityEntryLifecycleStatus::Active
            && ! $entryConflicted
            && $sourceValid
            && $sourceOperative
            && $governingPolicyOperative
            && $holderRuleValid
            && $eligibleHolderKeys !== []
            && $temporalValid
            && $scopeValid
            && $evidenceValid;
        $resolutionStatus = match (true) {
            $entryConflicted => 'conflicted',
            $lifecycle === AuthorityEntryLifecycleStatus::Design => 'design_only',
            $candidateHolderKeys === [] => 'vacant_holder',
            $lifecycle === AuthorityEntryLifecycleStatus::Approved || $effectiveFrom === null => 'pending_activation',
            $isEffective => 'effective',
            default => 'blocked',
        };
        $resolutionCounts[$resolutionStatus]++;

        return [
            ...$entry,
            'lifecycle_status_label' => $lifecycle?->label() ?? 'Invalid',
            'domain_label' => $action['domain_label'] ?? $domainKey,
            'action_label' => $action['label'] ?? $actionKey,
            'action_stage' => $action['stage'] ?? 'invalid',
            'source_label' => $sourceLabel,
            'source_operative' => $sourceOperative,
            'candidate_holder_keys' => $candidateHolderKeys,
            'candidate_holder_names' => $candidateHolderNames,
            'effective_holder_keys' => $isEffective ? $eligibleHolderKeys : [],
            'effective_holder_names' => $isEffective ? array_values(array_filter(
                $candidateHolderNames,
                static fn (string $name, int $index): bool => in_array($candidateHolderKeys[$index], $eligibleHolderKeys, true),
                ARRAY_FILTER_USE_BOTH,
            )) : [],
            'effective_at_resolved' => $effectiveFrom?->toIso8601String(),
            'temporal_state' => $this->temporalState($effectiveFrom, $expiresAt, $asOf),
            'resolution_status' => $resolutionStatus,
            'grants_firm_authority' => $isEffective,
            'authorizes_client_action' => false,
            'client_mandate_gate' => ($entry['scope']['client_mandate_required'] ?? false) === true ? 'required_separately' : 'not_applicable',
            'specific_approval_gate' => ($entry['scope']['specific_approval_required'] ?? false) === true ? 'required_separately' : 'not_applicable',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return array<string, array<string, mixed>>
     */
    private function validateDomains(array $domains, array &$conflicts): array
    {
        $actions = [];
        $domainKeys = [];

        foreach ($domains as $domain) {
            $domainKey = (string) ($domain['key'] ?? '');
            if ($domainKey === '' || in_array($domainKey, $domainKeys, true)) {
                $conflicts[] = $this->issue('invalid_authority_domain', 'An Authority domain has a missing or duplicate key.');
            }
            $domainKeys[] = $domainKey;
            foreach ($domain['actions'] ?? [] as $action) {
                $actionKey = (string) ($action['key'] ?? '');
                $indexKey = "{$domainKey}.{$actionKey}";
                if ($actionKey === '' || isset($actions[$indexKey]) || ! in_array($action['stage'] ?? null, ['decision', 'approval', 'execution', 'verification'], true)) {
                    $conflicts[] = $this->issue('invalid_authority_action', "Authority domain {$domainKey} has an invalid or duplicate action.");
                }
                $actions[$indexKey] = [...$action, 'domain_label' => $domain['label'] ?? $domainKey];
            }
        }

        return $actions;
    }

    /**
     * @param  array<string, string>  $definition
     * @param  array<string, array<string, mixed>>  $policies
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $sourceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $sourceGaps
     *
     * @return array<string, mixed>
     */
    private function resolveGoverningPolicy(array $definition, array $policies, Carbon $asOf, array &$conflicts, array &$sourceGaps): array
    {
        $key = $definition['key'] ?? '';
        $version = $definition['version'] ?? '';
        $policy = $policies[$key] ?? null;
        if ($policy === null || ($policy['current_version'] ?? null) !== $version) {
            $conflicts[] = $this->issue('invalid_authority_matrix_policy', 'The Authority Matrix does not resolve to the exact current governing policy version.');
        }
        $operative = $policy !== null && $this->policyVersionOperative($policy, $version, $asOf);
        if (! $operative) {
            $sourceGaps[] = $this->issue('authority_matrix_policy_not_effective', 'The Authority and Delegation Policy version governing this Matrix is not Effective.');
        }

        return [
            ...$definition,
            'title' => $policy['title'] ?? $key,
            'status' => $policy['current_status'] ?? 'missing',
            'status_label' => $policy['current_status_label'] ?? 'Missing',
            'operative' => $operative,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>|null  $requirement
     * @param  array<string, array<string, mixed>>  $policies
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $sourceGaps
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, message: string}> $sourceGaps
     *
     * @return array{bool, bool, string, Carbon|null}
     */
    private function resolveEntrySource(array $entry, ?array $requirement, array $policies, Carbon $asOf, array &$conflicts, array &$sourceGaps): array
    {
        $key = (string) ($entry['key'] ?? '');
        $source = is_array($entry['authority_source'] ?? null) ? $entry['authority_source'] : [];
        $requirementSource = is_array($requirement['source'] ?? null) ? $requirement['source'] : [];
        $type = $source['type'] ?? null;
        $valid = $requirement !== null && $type === ($requirementSource['type'] ?? null);
        $operative = false;
        $label = 'Invalid source';
        $effectiveAt = null;

        if ($type === 'constitution') {
            $valid = $valid && ($source['reference'] ?? null) === ($requirementSource['reference'] ?? null);
            $operative = $valid && ($requirement['source_status'] ?? null) === 'operative';
            $label = (string) ($requirement['source_label'] ?? 'Partnership Constitution');
        } elseif ($type === 'policy') {
            $policyKey = (string) ($source['key'] ?? '');
            $version = (string) ($source['version'] ?? '');
            $policy = $policies[$policyKey] ?? null;
            $valid = $valid && $policyKey === ($requirementSource['key'] ?? null)
                && $policy !== null && ($policy['current_version'] ?? null) === $version;
            $operative = $valid && $this->policyVersionOperative($policy, $version, $asOf);
            $label = ($policy['title'] ?? $policyKey)." v{$version}";
            $effectiveAt = $this->date($policy['current']['effective_at'] ?? null);
            if (! $operative) {
                $sourceGaps[] = $this->issue('authority_source_policy_not_effective', "Authority entry {$key} is governed by a policy version that is not Effective.");
            }
        } else {
            $valid = false;
        }

        if (! $valid) {
            $conflicts[] = $this->issue('authority_source_mismatch', "Authority entry {$key} does not match its Responsibility Coverage source.");
        }

        return [$valid, $operative, $label, $effectiveAt];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>|null  $requirement
     * @param  array<string, array<string, mixed>>  $identities
     * @param  array<string, array<string, mixed>>  $roles
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return array{list<string>, list<string>, bool}
     */
    private function resolveHolders(array $entry, ?array $requirement, array $identities, array $roles, array $assignments, array &$conflicts): array
    {
        $key = (string) ($entry['key'] ?? '');
        $holderRule = is_array($entry['holder_rule'] ?? null) ? $entry['holder_rule'] : [];
        $type = $holderRule['type'] ?? null;
        $ruleKey = (string) ($holderRule['key'] ?? '');
        $candidateKeys = [];
        $eligibleKeys = [];
        $valid = true;

        if ($type === 'partner_status') {
            foreach ($identities as $identity) {
                if (($identity['partner_status'] ?? null) === $ruleKey && ($identity['lifecycle_status'] ?? null) === 'recognized') {
                    $candidateKeys[] = $identity['key'];
                    $eligibleKeys[] = $identity['key'];
                }
            }
            $valid = ($requirement['authority_attachment'] ?? null) === 'partner_status';
        } elseif ($type === 'role') {
            $role = $roles[$ruleKey] ?? null;
            $valid = $role !== null
                && ($role['responsibility_requirement_key'] ?? null) === ($entry['responsibility_requirement_key'] ?? null)
                && in_array($role['authority_attachment'] ?? null, ['office', 'delegation'], true);
            [$candidateKeys, $eligibleKeys] = $this->assignmentHolders($assignments, $ruleKey);
        } elseif ($type === 'requirement_holders') {
            $candidateKeys = array_values(array_filter(
                $requirement['holder_keys'] ?? [],
                static fn (string $holderKey): bool => isset($identities[$holderKey]),
            ));
            $matchingRole = null;
            foreach ($roles as $role) {
                if (($role['responsibility_requirement_key'] ?? null) === $ruleKey) {
                    $matchingRole = $role;
                    break;
                }
            }
            if ($matchingRole !== null && in_array($matchingRole['authority_attachment'] ?? null, ['office', 'delegation'], true)) {
                [, $eligibleKeys] = $this->assignmentHolders($assignments, (string) $matchingRole['key']);
            }
            $valid = $ruleKey === ($entry['responsibility_requirement_key'] ?? null);
        } else {
            $valid = false;
        }

        if (! $valid) {
            $conflicts[] = $this->issue('invalid_authority_holder_rule', "Authority entry {$key} has a holder rule that cannot carry its authority.");
        }

        return [array_values(array_unique($candidateKeys)), array_values(array_unique($eligibleKeys)), $valid];
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @return array{list<string>, list<string>}
     */
    private function assignmentHolders(array $assignments, string $roleKey): array
    {
        $current = array_values(array_filter(
            $assignments,
            static fn (array $assignment): bool => ($assignment['role_key'] ?? null) === $roleKey
                && ! in_array($assignment['lifecycle_status'] ?? null, ['ended', 'revoked'], true),
        ));

        return [
            array_values(array_unique(array_column($current, 'identity_key'))),
            array_values(array_unique(array_column(array_filter(
                $current,
                static fn (array $assignment): bool => ($assignment['grants_firm_authority'] ?? false) === true,
            ), 'identity_key'))),
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>|null  $action
     * @param  list<array{code: string, message: string}>  $boundaryGaps
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $boundaryGaps
     * @param-out list<array{code: string, message: string}> $conflicts
     */
    private function validateBoundary(array $entry, ?array $action, array &$boundaryGaps, array &$conflicts): bool
    {
        $key = (string) ($entry['key'] ?? '');
        $scope = $entry['scope'] ?? null;
        $separation = $entry['separation'] ?? null;
        $delegation = $entry['delegation'] ?? null;
        $valid = $this->completeRecord($scope, ['authority_boundary', 'client_mandate_required', 'specific_approval_required', 'risk_boundary', 'thresholds', 'exclusions'])
            && is_bool($scope['client_mandate_required'] ?? null)
            && is_bool($scope['specific_approval_required'] ?? null)
            && is_array($scope['thresholds'] ?? null)
            && is_array($scope['exclusions'] ?? null)
            && $this->completeRecord($separation, ['self_approval_permitted', 'execution_separate', 'independent_verification_required'])
            && is_bool($separation['self_approval_permitted'] ?? null)
            && is_bool($separation['execution_separate'] ?? null)
            && is_bool($separation['independent_verification_required'] ?? null)
            && $this->completeRecord($delegation, ['permitted', 'subdelegation_permitted', 'requires_explicit_assignment', 'maximum_duration_days'], true)
            && is_bool($delegation['permitted'] ?? null)
            && is_bool($delegation['subdelegation_permitted'] ?? null)
            && is_bool($delegation['requires_explicit_assignment'] ?? null)
            && (($delegation['maximum_duration_days'] ?? null) === null || is_int($delegation['maximum_duration_days']));
        if (! $valid || ($scope['authority_boundary'] ?? null) !== 'firm_authority_only') {
            $boundaryGaps[] = $this->issue('incomplete_authority_boundary', "Authority entry {$key} lacks a complete Firm Authority boundary.");

            return false;
        }
        $thresholds = $scope['thresholds'];
        $monetaryStatus = $thresholds['monetary_status'] ?? null;
        if (! in_array($monetaryStatus, ['not_applicable', 'resolved', 'unresolved'], true)) {
            $conflicts[] = $this->issue('invalid_authority_threshold_state', "Authority entry {$key} has an invalid threshold state.");

            return false;
        }
        if ($monetaryStatus === 'unresolved') {
            $boundaryGaps[] = $this->issue('authority_threshold_unresolved', "Authority entry {$key} has an unresolved monetary threshold.");

            return false;
        }
        if ($monetaryStatus === 'resolved' && ! is_numeric($thresholds['monetary_limit'] ?? null)) {
            $conflicts[] = $this->issue('missing_resolved_authority_threshold', "Authority entry {$key} marks its monetary threshold resolved without a value.");

            return false;
        }
        if (($delegation['permitted'] ?? null) === false && ($delegation['subdelegation_permitted'] ?? null) === true) {
            $conflicts[] = $this->issue('invalid_subdelegation', "Authority entry {$key} permits subdelegation while delegation is prohibited.");

            return false;
        }
        if (($action['stage'] ?? null) === 'approval' && ($separation['self_approval_permitted'] ?? null) === true) {
            $conflicts[] = $this->issue('self_approval_authority', "Authority entry {$key} permits self-approval for a controlled approval action.");

            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $policy */
    private function policyVersionOperative(array $policy, string $version, Carbon $asOf): bool
    {
        $current = is_array($policy['current'] ?? null) ? $policy['current'] : [];
        $effectiveAt = $this->date($current['effective_at'] ?? null);

        return ($policy['current_version'] ?? null) === $version
            && ($policy['current_status'] ?? null) === 'effective'
            && ($current['status'] ?? null) === 'effective'
            && $effectiveAt !== null
            && ! $effectiveAt->isAfter($asOf);
    }

    /** @param array<string, mixed> $entry */
    private function resolveEffectiveFrom(array $entry, ?Carbon $firmEffectiveAt, ?Carbon $sourceEffectiveAt): ?Carbon
    {
        return match ($entry['effective_at_source'] ?? null) {
            'formation.firm.effective_date' => $firmEffectiveAt,
            'authority_source.effective_at' => $sourceEffectiveAt,
            default => $this->date($entry['effective_at'] ?? null),
        };
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
                $conflicts[] = $this->issue('invalid_authority_evidence_key', 'An Authority Evidence Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (! $this->completeRecord($record, ['record_type', 'actor', 'occurred_at', 'source', 'reason', 'approval', 'state', 'supporting_evidence'])) {
                $evidenceGaps[] = $this->issue('incomplete_authority_evidence', "Authority Evidence Record {$key} is incomplete.");
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
            $evidenceGaps[] = $this->issue('missing_authority_evidence', "{$subject} lacks a complete linked Evidence Record.");
        }

        return $valid;
    }

    /** @param list<string> $fields */
    private function completeRecord(mixed $record, array $fields, bool $allowNull = false): bool
    {
        if (! is_array($record)) {
            return false;
        }
        foreach ($fields as $field) {
            if (! array_key_exists($field, $record)) {
                return false;
            }
            if (! $allowNull && ($record[$field] === null || $record[$field] === '' || $record[$field] === [])) {
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

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
