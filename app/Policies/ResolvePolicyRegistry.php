<?php

namespace App\Policies;

use App\DecisionRecords\ResolvedDecisionRecords;
use App\FormationBootstrap\ResolvedFormationBootstrap;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

final class ResolvePolicyRegistry
{
    /**
     * Resolve repository-backed policy truth without inferring approval or authority.
     */
    public function handle(
        PolicyRegistryDefinition $definition,
        ?DateTimeImmutable $asOf = null,
        ?ResolvedDecisionRecords $decisionRecords = null,
        ?ResolvedFormationBootstrap $formationBootstrap = null,
    ): ResolvedPolicyRegistry {
        $conflicts = [];
        $lifecycleGaps = [];
        $evidenceGaps = [];
        $admissionGaps = [];
        $activationGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $statusCounts = array_fill_keys(array_column(PolicyLifecycleStatus::cases(), 'value'), 0);
        $evidenceKeys = $this->uniqueKeys(
            records: $definition->evidenceRecords,
            recordType: 'evidence record',
            conflicts: $conflicts,
        );
        $this->validateEvidenceRecords($definition->evidenceRecords, $evidenceGaps);
        $policyVersionIndex = $this->policyVersionIndex($definition->policies);
        $bootstrapApprovalIndex = $this->bootstrapApprovalIndex($formationBootstrap);
        $decisionCandidates = $this->policyDecisionCandidates($decisionRecords, $policyVersionIndex);
        $approvalAdmissions = $this->resolveApprovalAdmissions(
            $definition->approvalAdmissions,
            $decisionCandidates,
            $policyVersionIndex,
            $evidenceKeys,
            $effectiveAt,
            $conflicts,
            $admissionGaps,
            $evidenceGaps,
        );
        $approvalAdmissionIndex = $this->indexByKey($approvalAdmissions);
        $publicationRecords = $this->resolvePublicationRecords(
            $definition->publicationRecords,
            $policyVersionIndex,
            $evidenceKeys,
            $effectiveAt,
            $conflicts,
            $activationGaps,
            $evidenceGaps,
        );
        $publicationIndex = $this->indexByKey($publicationRecords);
        $activationRecords = $this->resolveActivationRecords(
            $definition->activationRecords,
            $policyVersionIndex,
            $approvalAdmissionIndex,
            $bootstrapApprovalIndex,
            $publicationIndex,
            $evidenceKeys,
            $effectiveAt,
            $conflicts,
            $activationGaps,
            $evidenceGaps,
        );
        $activationIndex = $this->indexByKey($activationRecords);
        $admittedDecisionKeys = array_column(array_filter($approvalAdmissions, static fn (array $record): bool => $record['grants_policy_approval_basis'] === true), 'decision_record_key');
        $availableDecisionCandidates = array_values(array_filter($decisionCandidates, static fn (array $candidate): bool => ! in_array($candidate['key'], $admittedDecisionKeys, true)));
        $policyKeys = [];
        $resolvedPolicies = [];

        foreach ($definition->policies as $policy) {
            if (in_array($policy->key, $policyKeys, true)) {
                $conflicts[] = $this->issue(
                    'duplicate_policy_key',
                    "Policy key {$policy->key} is duplicated.",
                );
            }

            $policyKeys[] = $policy->key;
            $versionsByNumber = [];

            foreach ($policy->versions as $version) {
                $versionNumber = (string) ($version['version'] ?? '');

                if (isset($versionsByNumber[$versionNumber])) {
                    $conflicts[] = $this->issue(
                        'duplicate_policy_version',
                        "{$policy->title} has duplicate version {$versionNumber}.",
                    );
                }

                $versionsByNumber[$versionNumber] = $version;
            }

            $currentVersion = $versionsByNumber[$policy->currentVersion] ?? null;

            if ($currentVersion === null) {
                $conflicts[] = $this->issue(
                    'missing_current_policy_version',
                    "{$policy->title} points to missing current version {$policy->currentVersion}.",
                );

                continue;
            }

            $resolvedVersions = [];

            foreach ($policy->versions as $version) {
                $resolvedVersions[] = $this->resolveVersion(
                    policy: $policy,
                    version: $version,
                    versionsByNumber: $versionsByNumber,
                    evidenceKeys: $evidenceKeys,
                    approvalAdmissions: $approvalAdmissionIndex,
                    bootstrapApprovals: $bootstrapApprovalIndex,
                    publications: $publicationIndex,
                    activations: $activationIndex,
                    asOf: $effectiveAt,
                    conflicts: $conflicts,
                    lifecycleGaps: $lifecycleGaps,
                    evidenceGaps: $evidenceGaps,
                );
            }

            $currentStatus = PolicyLifecycleStatus::tryFrom((string) ($currentVersion['status'] ?? ''));

            if ($currentStatus !== null) {
                $statusCounts[$currentStatus->value]++;
            }

            $resolvedPolicies[] = [
                'key' => $policy->key,
                'title' => $policy->title,
                'owner' => $policy->owner,
                'approving_authority' => $policy->approvingAuthority,
                'current_version' => $policy->currentVersion,
                'current_status' => $currentStatus === null ? 'invalid' : $currentStatus->value,
                'current_status_label' => $currentStatus === null ? 'Invalid' : $currentStatus->label(),
                'current' => collect($resolvedVersions)->firstWhere('version', $policy->currentVersion),
                'versions' => $resolvedVersions,
            ];
        }

        $resolvedExceptions = $this->resolveExceptions(
            exceptions: $definition->exceptions,
            policies: $definition->policies,
            evidenceKeys: $evidenceKeys,
            asOf: $effectiveAt,
            conflicts: $conflicts,
            lifecycleGaps: $lifecycleGaps,
            evidenceGaps: $evidenceGaps,
        );

        return new ResolvedPolicyRegistry(
            schemaVersion: $definition->schemaVersion,
            policies: $resolvedPolicies,
            approvalAdmissions: $approvalAdmissions,
            publicationRecords: $publicationRecords,
            activationRecords: $activationRecords,
            exceptions: $resolvedExceptions,
            evidenceRecords: $definition->evidenceRecords,
            statusCounts: $statusCounts,
            conflicts: $conflicts,
            lifecycleGaps: $lifecycleGaps,
            evidenceGaps: $evidenceGaps,
            admissionGaps: $admissionGaps,
            activationGaps: $activationGaps,
            availableDecisionCandidates: $availableDecisionCandidates,
        );
    }

    /**
     * @param  array<string, mixed>  $version
     * @param  array<string, array<string, mixed>>  $versionsByNumber
     * @param  list<string>  $evidenceKeys
     * @param  array<string, array<string, mixed>>  $approvalAdmissions
     * @param  array<string, array<string, mixed>>  $bootstrapApprovals
     * @param  array<string, array<string, mixed>>  $publications
     * @param  array<string, array<string, mixed>>  $activations
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $lifecycleGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveVersion(
        PolicyDefinition $policy,
        array $version,
        array $versionsByNumber,
        array $evidenceKeys,
        array $approvalAdmissions,
        array $bootstrapApprovals,
        array $publications,
        array $activations,
        Carbon $asOf,
        array &$conflicts,
        array &$lifecycleGaps,
        array &$evidenceGaps,
    ): array {
        $versionNumber = (string) ($version['version'] ?? '');
        $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
        $documentPath = (string) ($version['document_path'] ?? '');
        $targetKey = $this->policyVersionKey($policy->key, $versionNumber);
        $approvalAdmission = $approvalAdmissions[$version['approval_admission_key'] ?? ''] ?? null;
        $bootstrapApproval = $bootstrapApprovals[$targetKey] ?? null;
        $formationRatificationKey = $version['formation_ratification_key'] ?? null;
        $bootstrapApprovalVerified = ($bootstrapApproval['grants_initial_policy_approval_basis'] ?? false) === true
            && ($bootstrapApproval['ratification_record_key'] ?? null) === $formationRatificationKey;
        $publication = $publications[$version['publication_record_key'] ?? ''] ?? null;
        $activation = $activations[$version['activation_record_key'] ?? ''] ?? null;

        if ($status === null) {
            $conflicts[] = $this->issue(
                'invalid_policy_status',
                "{$policy->title} version {$versionNumber} has an invalid lifecycle status.",
            );
        }

        $contentIntegrity = $this->contentIntegrity(
            policy: $policy,
            versionNumber: $versionNumber,
            documentPath: $documentPath,
            expectedDigest: (string) ($version['content_digest'] ?? ''),
            status: $status,
            conflicts: $conflicts,
        );

        if ($status?->requiresApproval() === true && ($approvalAdmission['grants_policy_approval_basis'] ?? false) !== true && ! $bootstrapApprovalVerified) {
            $lifecycleGaps[] = $this->issue(
                'missing_policy_approval',
                "{$policy->title} version {$versionNumber} is {$status->label()} without an admitted effective Decision Record or verified Formation Ratification basis.",
            );
        }

        if ($approvalAdmission !== null && ($approvalAdmission['target_key'] ?? null) !== $targetKey) {
            $conflicts[] = $this->issue('policy_approval_target_mismatch', "{$policy->title} version {$versionNumber} cites an approval admitted for another Policy Version.");
        }

        if ($status === PolicyLifecycleStatus::Effective && empty($version['effective_at'])) {
            $lifecycleGaps[] = $this->issue(
                'missing_effective_date',
                "{$policy->title} version {$versionNumber} is Effective without an effective date.",
            );
        }
        if ($status === PolicyLifecycleStatus::Effective) {
            if (($publication['publication_verified'] ?? false) !== true || ($publication['target_key'] ?? null) !== $targetKey) {
                $lifecycleGaps[] = $this->issue('missing_policy_publication', "{$policy->title} version {$versionNumber} is Effective without a verified publication record.");
            }
            if (($activation['activation_verified'] ?? false) !== true || ($activation['target_key'] ?? null) !== $targetKey) {
                $lifecycleGaps[] = $this->issue('missing_policy_activation', "{$policy->title} version {$versionNumber} is Effective without a verified activation record.");
            } elseif (($activation['effective_at'] ?? null) !== ($version['effective_at'] ?? null)) {
                $conflicts[] = $this->issue('policy_activation_date_mismatch', "{$policy->title} version {$versionNumber} contradicts its activation effective date.");
            }
        }

        if ($status === PolicyLifecycleStatus::Superseded) {
            $supersededBy = (string) ($version['superseded_by'] ?? '');

            if ($supersededBy === '' || ! isset($versionsByNumber[$supersededBy])) {
                $conflicts[] = $this->issue(
                    'missing_superseding_version',
                    "{$policy->title} version {$versionNumber} is Superseded without a retained superseding version.",
                );
            }
        }

        $effectiveDate = $this->date($version['effective_at'] ?? null);
        $operative = $status === PolicyLifecycleStatus::Effective
            && $contentIntegrity === 'verified'
            && (($approvalAdmission['grants_policy_approval_basis'] ?? false) === true || $bootstrapApprovalVerified)
            && ($publication['publication_verified'] ?? false) === true
            && ($activation['activation_verified'] ?? false) === true
            && $effectiveDate !== null
            && ! $effectiveDate->isAfter($asOf);

        return [
            ...$version,
            'status_label' => $status?->label() ?? 'Invalid',
            'content_integrity' => $contentIntegrity,
            'approval_admitted' => ($approvalAdmission['grants_policy_approval_basis'] ?? false) === true,
            'formation_ratified' => $bootstrapApprovalVerified,
            'publication_verified' => ($publication['publication_verified'] ?? false) === true,
            'activation_verified' => ($activation['activation_verified'] ?? false) === true,
            'operative' => $operative,
        ];
    }

    /**
     * @param  list<PolicyDefinition>  $policies
     * @return array<string, array<string, mixed>>
     */
    private function policyVersionIndex(array $policies): array
    {
        $index = [];
        foreach ($policies as $policy) {
            foreach ($policy->versions as $version) {
                $versionNumber = (string) ($version['version'] ?? '');
                $key = $this->policyVersionKey($policy->key, $versionNumber);
                $index[$key] = [
                    'key' => $key,
                    'policy_key' => $policy->key,
                    'policy_title' => $policy->title,
                    'policy_version' => $versionNumber,
                    'document_path' => $version['document_path'] ?? null,
                    'content_digest' => $version['content_digest'] ?? null,
                    'effective_at' => $version['effective_at'] ?? null,
                ];
            }
        }

        return $index;
    }

    /** @return array<string, array<string, mixed>> */
    private function bootstrapApprovalIndex(?ResolvedFormationBootstrap $formationBootstrap): array
    {
        $index = [];
        foreach ($formationBootstrap->policyApprovals ?? [] as $approval) {
            if (is_string($approval['target_key'] ?? null)) {
                $index[$approval['target_key']] = $approval;
            }
        }

        return $index;
    }

    /**
     * @param  array<string, array<string, mixed>>  $policyVersions
     * @return list<array<string, mixed>>
     */
    private function policyDecisionCandidates(?ResolvedDecisionRecords $decisionRecords, array $policyVersions): array
    {
        if ($decisionRecords === null) {
            return [];
        }

        $candidates = [];
        foreach ($decisionRecords->decisions as $decision) {
            if (($decision['institutionally_valid'] ?? false) !== true) {
                continue;
            }
            foreach ($decision['context']['reference_keys'] ?? [] as $referenceKey) {
                if (isset($policyVersions[$referenceKey])) {
                    $candidates[] = [
                        ...$decision,
                        'candidate_key' => $decision['key'].'::'.$referenceKey,
                        'target_key' => $referenceKey,
                    ];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array<string, mixed>>  $candidates
     * @param  array<string, array<string, mixed>>  $policyVersions
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $admissionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveApprovalAdmissions(array $records, array $candidates, array $policyVersions, array $evidenceKeys, Carbon $asOf, array &$conflicts, array &$admissionGaps, array &$evidenceGaps): array
    {
        $candidateIndex = [];
        foreach ($candidates as $candidate) {
            $candidateIndex[$candidate['candidate_key']] = $candidate;
        }
        $keys = [];
        $resolved = [];
        $decisionOccurrences = array_count_values(array_column($records, 'decision_record_key'));
        $targetOccurrences = array_count_values(array_map(fn (array $record): string => $this->policyVersionKey((string) ($record['policy_key'] ?? ''), (string) ($record['policy_version'] ?? '')), $records));

        foreach ($records as $record) {
            $issueCount = count($conflicts) + count($admissionGaps) + count($evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $decisionKey = (string) ($record['decision_record_key'] ?? '');
            $targetKey = $this->policyVersionKey((string) ($record['policy_key'] ?? ''), (string) ($record['policy_version'] ?? ''));
            $candidate = $candidateIndex[$decisionKey.'::'.$targetKey] ?? null;
            $status = PolicyApprovalAdmissionStatus::tryFrom((string) ($record['status'] ?? ''));

            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_policy_approval_admission_key', 'A Policy Approval Admission has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (($decisionOccurrences[$decisionKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_policy_approval_decision', "Decision Record {$decisionKey} is admitted more than once.");
            }
            if (($targetOccurrences[$targetKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_policy_version_approval', "Policy Version {$targetKey} receives more than one approval admission.");
            }
            if ($status === null) {
                $conflicts[] = $this->issue('invalid_policy_approval_admission_status', "Policy Approval Admission {$key} has an invalid status.");
            }
            if (! isset($policyVersions[$targetKey])) {
                $admissionGaps[] = $this->issue('policy_approval_target_missing', "Policy Approval Admission {$key} references an unknown Policy Version.");
            }
            if ($candidate === null) {
                $admissionGaps[] = $this->issue('effective_policy_decision_missing', "Policy Approval Admission {$key} lacks an exact effective Decision Record candidate.");
            } elseif (($record['decision_snapshot'] ?? null) !== $this->policyDecisionSnapshot($candidate)) {
                $conflicts[] = $this->issue('policy_decision_snapshot_mismatch', "Policy Approval Admission {$key} contradicts its Decision Record source.");
            }
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('missing_policy_admission_evidence', "Policy Approval Admission {$key} lacks a complete Evidence Record.");
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            $decidedAt = $this->date($candidate['decision']['decided_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf) || ($decidedAt !== null && $recordedAt->isBefore($decidedAt))) {
                $conflicts[] = $this->issue('invalid_policy_admission_time', "Policy Approval Admission {$key} has an invalid recording time.");
            }

            $resolved[] = [
                ...$record,
                'status_label' => $status?->label() ?? 'Invalid',
                'target_key' => $targetKey,
                'grants_policy_approval_basis' => $status === PolicyApprovalAdmissionStatus::Admitted
                    && count($conflicts) + count($admissionGaps) + count($evidenceGaps) === $issueCount,
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $policyVersions
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $activationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolvePublicationRecords(array $records, array $policyVersions, array $evidenceKeys, Carbon $asOf, array &$conflicts, array &$activationGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $keys = [];
        $targets = array_map(fn (array $record): string => $this->policyVersionKey((string) ($record['policy_key'] ?? ''), (string) ($record['policy_version'] ?? '')), $records);
        $targetOccurrences = array_count_values($targets);
        foreach ($records as $index => $record) {
            $issueCount = count($conflicts) + count($activationGaps) + count($evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $targetKey = $targets[$index];
            $version = $policyVersions[$targetKey] ?? null;
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_policy_publication_key', 'A Policy Publication Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (($targetOccurrences[$targetKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_policy_publication', "Policy Version {$targetKey} has more than one publication record.");
            }
            if ($version === null) {
                $activationGaps[] = $this->issue('policy_publication_target_missing', "Policy Publication {$key} references an unknown Policy Version.");
            } elseif (($record['document_path'] ?? null) !== $version['document_path'] || ($record['content_digest'] ?? null) !== $version['content_digest']) {
                $conflicts[] = $this->issue('policy_publication_content_mismatch', "Policy Publication {$key} does not preserve the exact controlled document and digest.");
            }
            if (empty($record['published_by_identity_key']) || ! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('incomplete_policy_publication', "Policy Publication {$key} lacks attribution or complete Evidence.");
            }
            $publishedAt = $this->date($record['published_at'] ?? null);
            if ($publishedAt === null || $publishedAt->isAfter($asOf)) {
                $conflicts[] = $this->issue('invalid_policy_publication_time', "Policy Publication {$key} has an invalid publication time.");
            }
            $resolved[] = [...$record, 'target_key' => $targetKey, 'publication_verified' => count($conflicts) + count($activationGaps) + count($evidenceGaps) === $issueCount];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $policyVersions
     * @param  array<string, array<string, mixed>>  $approvalAdmissions
     * @param  array<string, array<string, mixed>>  $bootstrapApprovals
     * @param  array<string, array<string, mixed>>  $publications
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $activationGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveActivationRecords(array $records, array $policyVersions, array $approvalAdmissions, array $bootstrapApprovals, array $publications, array $evidenceKeys, Carbon $asOf, array &$conflicts, array &$activationGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $keys = [];
        $targets = array_map(fn (array $record): string => $this->policyVersionKey((string) ($record['policy_key'] ?? ''), (string) ($record['policy_version'] ?? '')), $records);
        $targetOccurrences = array_count_values($targets);
        foreach ($records as $index => $record) {
            $issueCount = count($conflicts) + count($activationGaps) + count($evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $targetKey = $targets[$index];
            $version = $policyVersions[$targetKey] ?? null;
            $approval = $approvalAdmissions[$record['approval_admission_key'] ?? ''] ?? null;
            $bootstrapApproval = $bootstrapApprovals[$targetKey] ?? null;
            $publication = $publications[$record['publication_record_key'] ?? ''] ?? null;
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_policy_activation_key', 'A Policy Activation Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if (($targetOccurrences[$targetKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_policy_activation', "Policy Version {$targetKey} has more than one activation record.");
            }
            if ($version === null) {
                $activationGaps[] = $this->issue('policy_activation_target_missing', "Policy Activation {$key} references an unknown Policy Version.");
            }
            $regularApprovalValid = ($approval['grants_policy_approval_basis'] ?? false) === true && ($approval['target_key'] ?? null) === $targetKey;
            $bootstrapApprovalValid = ($bootstrapApproval['grants_initial_policy_approval_basis'] ?? false) === true
                && ($bootstrapApproval['ratification_record_key'] ?? null) === ($record['formation_ratification_key'] ?? null);
            if (! $regularApprovalValid && ! $bootstrapApprovalValid) {
                $activationGaps[] = $this->issue('activation_without_admitted_approval', "Policy Activation {$key} lacks the exact admitted approval basis.");
            }
            if (($publication['publication_verified'] ?? false) !== true || ($publication['target_key'] ?? null) !== $targetKey) {
                $activationGaps[] = $this->issue('activation_without_publication', "Policy Activation {$key} lacks the exact verified publication.");
            }
            if (($record['effective_at'] ?? null) !== ($version['effective_at'] ?? null)) {
                $conflicts[] = $this->issue('activation_effective_date_mismatch', "Policy Activation {$key} contradicts the Policy Version effective date.");
            }
            if (empty($record['activated_by_identity_key']) || ! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('incomplete_policy_activation', "Policy Activation {$key} lacks attribution or complete Evidence.");
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            $admissionRecordedAt = $this->date($approval['recorded_at'] ?? null);
            $ratifiedAt = $this->date($bootstrapApproval['ratified_at'] ?? null);
            $publishedAt = $this->date($publication['published_at'] ?? null);
            $effectiveDate = $this->date($record['effective_at'] ?? null);
            if ($recordedAt === null
                || $recordedAt->isAfter($asOf)
                || ($admissionRecordedAt !== null && $publishedAt !== null && $publishedAt->isBefore($admissionRecordedAt))
                || ($ratifiedAt !== null && $publishedAt !== null && $publishedAt->isBefore($ratifiedAt))
                || ($publishedAt !== null && $recordedAt->isBefore($publishedAt))
                || ($effectiveDate !== null && $effectiveDate->isBefore($recordedAt))) {
                $conflicts[] = $this->issue('invalid_policy_activation_time', "Policy Activation {$key} has an invalid chronology.");
            }
            $resolved[] = [...$record, 'target_key' => $targetKey, 'activation_verified' => count($conflicts) + count($activationGaps) + count($evidenceGaps) === $issueCount];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $decision
     * @return array<string, mixed>
     */
    private function policyDecisionSnapshot(array $decision): array
    {
        return [
            'title' => $decision['title'],
            'context' => $decision['context'],
            'outcome' => $decision['decision']['outcome'],
            'decided_at' => $decision['decision']['decided_at'],
            'effective_at' => $decision['decision']['effective_at'],
            'evidence_record_key' => $decision['decision']['evidence_record_key'],
            'authority_basis_type' => $decision['authority_basis_type'],
        ];
    }

    /** @param list<string> $evidenceKeys */
    private function hasEvidence(mixed $key, array $evidenceKeys): bool
    {
        return is_string($key) && in_array($key, $evidenceKeys, true);
    }

    private function policyVersionKey(string $policyKey, string $version): string
    {
        return "policy:{$policyKey}:{$version}";
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $index = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $index[$record['key']] = $record;
            }
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $exceptions
     * @param  list<PolicyDefinition>  $policies
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $lifecycleGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveExceptions(
        array $exceptions,
        array $policies,
        array $evidenceKeys,
        Carbon $asOf,
        array &$conflicts,
        array &$lifecycleGaps,
        array &$evidenceGaps,
    ): array {
        $resolved = [];
        $exceptionKeys = [];

        foreach ($exceptions as $exception) {
            $key = (string) ($exception['key'] ?? '');
            $policyKey = (string) ($exception['policy_key'] ?? '');
            $versionNumber = (string) ($exception['policy_version'] ?? '');
            $status = PolicyExceptionStatus::tryFrom((string) ($exception['status'] ?? ''));
            $policy = collect($policies)->firstWhere('key', $policyKey);

            if (in_array($key, $exceptionKeys, true)) {
                $conflicts[] = $this->issue('duplicate_exception_key', "Policy exception {$key} is duplicated.");
            }

            $exceptionKeys[] = $key;

            if ($status === null) {
                $conflicts[] = $this->issue('invalid_exception_status', "Policy exception {$key} has an invalid status.");
            }

            if (! $policy instanceof PolicyDefinition) {
                $conflicts[] = $this->issue('missing_exception_policy', "Policy exception {$key} references an unknown policy.");
            } else {
                $policyVersion = collect($policy->versions)->firstWhere('version', $versionNumber);

                if (! is_array($policyVersion)) {
                    $conflicts[] = $this->issue('missing_exception_version', "Policy exception {$key} references an unknown policy version.");
                } else {
                    $policyStatus = PolicyLifecycleStatus::tryFrom((string) ($policyVersion['status'] ?? ''));

                    if (! in_array($policyStatus, [PolicyLifecycleStatus::Approved, PolicyLifecycleStatus::Effective], true)) {
                        $conflicts[] = $this->issue('exception_on_inoperative_policy', "Policy exception {$key} is attached to a policy version that is not Approved or Effective.");
                    }
                }
            }

            $approval = $exception['approval'] ?? null;

            if ($status?->requiresApproval() === true && ! is_array($approval)) {
                $lifecycleGaps[] = $this->issue('missing_exception_approval', "Policy exception {$key} lacks its own approval record.");
            }

            if (is_array($approval)) {
                if (($approval['outcome'] ?? null) !== 'approved' && $status?->requiresApproval() === true) {
                    $conflicts[] = $this->issue('exception_approval_outcome_conflict', "Policy exception {$key} requires an approved outcome.");
                }

                $this->validateApprovalEvidence(
                    subject: "Policy exception {$key}",
                    approval: $approval,
                    evidenceKeys: $evidenceKeys,
                    evidenceGaps: $evidenceGaps,
                );
            }

            $effectiveAt = $this->date($exception['effective_at'] ?? null);
            $expiresAt = $this->date($exception['expires_at'] ?? null);
            $reviewAt = $this->date($exception['review_at'] ?? null);

            if ($status?->requiresApproval() === true && ($effectiveAt === null || $expiresAt === null || $reviewAt === null)) {
                $lifecycleGaps[] = $this->issue('incomplete_exception_dates', "Policy exception {$key} requires effective, review, and expiry dates.");
            }

            if (
                empty($exception['specific_requirement'])
                || empty($exception['reason'])
                || empty($exception['risk'])
                || empty($exception['compensating_controls'])
            ) {
                $lifecycleGaps[] = $this->issue('incomplete_exception_scope', "Policy exception {$key} must state the requirement, reason, risk, and compensating controls.");
            }

            if ($effectiveAt !== null && $expiresAt !== null && $expiresAt->lessThanOrEqualTo($effectiveAt)) {
                $conflicts[] = $this->issue('invalid_exception_expiry', "Policy exception {$key} does not expire after it becomes effective.");
            }

            if ($reviewAt !== null && $expiresAt !== null && $reviewAt->greaterThan($expiresAt)) {
                $conflicts[] = $this->issue('exception_review_after_expiry', "Policy exception {$key} is scheduled for review after expiry.");
            }

            if ($status === PolicyExceptionStatus::Active && $expiresAt?->lessThanOrEqualTo($asOf)) {
                $conflicts[] = $this->issue('active_exception_expired', "Policy exception {$key} remains Active after its approved expiry.");
            }

            $resolved[] = [
                ...$exception,
                'status_label' => $status === null ? 'Invalid' : Str::headline($status->value),
                'temporal_state' => $expiresAt?->lessThanOrEqualTo($asOf) ? 'past_expiry' : 'within_term',
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return list<string>
     */
    private function uniqueKeys(array $records, string $recordType, array &$conflicts): array
    {
        $keys = [];

        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');

            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_record_key', "A {$recordType} has a missing or duplicate key.");
            }

            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function contentIntegrity(
        PolicyDefinition $policy,
        string $versionNumber,
        string $documentPath,
        string $expectedDigest,
        ?PolicyLifecycleStatus $status,
        array &$conflicts,
    ): string {
        if ($documentPath === '' || ! Str::startsWith($documentPath, 'docs/policies/') || Str::contains($documentPath, '..')) {
            $conflicts[] = $this->issue('invalid_policy_document_path', "{$policy->title} version {$versionNumber} has an invalid document path.");

            return 'missing';
        }

        $absolutePath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$documentPath;

        if (! is_file($absolutePath)) {
            $conflicts[] = $this->issue('missing_policy_document', "{$policy->title} version {$versionNumber} has no source document.");

            return 'missing';
        }

        if ($status?->requiresImmutableContent() !== true) {
            return 'mutable_draft';
        }

        $actualDigest = hash_file('sha256', $absolutePath);

        if ($actualDigest === false || $expectedDigest === '' || ! hash_equals($expectedDigest, $actualDigest)) {
            $conflicts[] = $this->issue('policy_content_changed', "{$policy->title} version {$versionNumber} changed after submission for review.");

            return 'digest_mismatch';
        }

        return 'verified';
    }

    /**
     * @param  array<string, mixed>  $approval
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateApprovalEvidence(
        string $subject,
        array $approval,
        array $evidenceKeys,
        array &$evidenceGaps,
    ): void {
        $evidenceKey = (string) ($approval['evidence_record_key'] ?? '');

        if (empty($approval['approver']) || empty($approval['authority_basis']) || empty($approval['decided_at'])) {
            $evidenceGaps[] = $this->issue('incomplete_approval_record', "{$subject} has an incomplete approval record.");
        }

        if ($evidenceKey === '' || ! in_array($evidenceKey, $evidenceKeys, true)) {
            $evidenceGaps[] = $this->issue('missing_approval_evidence', "{$subject} approval is not linked to a known Evidence Record.");
        }
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateEvidenceRecords(array $records, array &$evidenceGaps): void
    {
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? 'unknown');

            if (
                empty($record['record_type'])
                || empty($record['subject'])
                || empty($record['actor'])
                || empty($record['recorded_at'])
                || empty($record['source'])
                || empty($record['reason'])
                || empty($record['state'])
            ) {
                $evidenceGaps[] = $this->issue('incomplete_evidence_record', "Evidence Record {$key} is incomplete.");
            }
        }
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
