<?php

namespace App\Policies;

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
    ): ResolvedPolicyRegistry {
        $conflicts = [];
        $lifecycleGaps = [];
        $evidenceGaps = [];
        $statusCounts = array_fill_keys(array_column(PolicyLifecycleStatus::cases(), 'value'), 0);
        $evidenceKeys = $this->uniqueKeys(
            records: $definition->evidenceRecords,
            recordType: 'evidence record',
            conflicts: $conflicts,
        );
        $this->validateEvidenceRecords($definition->evidenceRecords, $evidenceGaps);
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
            asOf: Carbon::instance($asOf ?? new DateTimeImmutable),
            conflicts: $conflicts,
            lifecycleGaps: $lifecycleGaps,
            evidenceGaps: $evidenceGaps,
        );

        return new ResolvedPolicyRegistry(
            schemaVersion: $definition->schemaVersion,
            policies: $resolvedPolicies,
            exceptions: $resolvedExceptions,
            evidenceRecords: $definition->evidenceRecords,
            statusCounts: $statusCounts,
            conflicts: $conflicts,
            lifecycleGaps: $lifecycleGaps,
            evidenceGaps: $evidenceGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $version
     * @param  array<string, array<string, mixed>>  $versionsByNumber
     * @param  list<string>  $evidenceKeys
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
        array &$conflicts,
        array &$lifecycleGaps,
        array &$evidenceGaps,
    ): array {
        $versionNumber = (string) ($version['version'] ?? '');
        $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
        $documentPath = (string) ($version['document_path'] ?? '');
        $approval = $version['approval'] ?? null;

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

        if ($status?->requiresApproval() === true && ! is_array($approval)) {
            $lifecycleGaps[] = $this->issue(
                'missing_policy_approval',
                "{$policy->title} version {$versionNumber} is {$status->label()} without an explicit approval record.",
            );
        }

        if (is_array($approval)) {
            if (($approval['outcome'] ?? null) !== 'approved' && $status?->requiresApproval() === true) {
                $conflicts[] = $this->issue(
                    'policy_approval_outcome_conflict',
                    "{$policy->title} version {$versionNumber} requires an approved outcome.",
                );
            }

            $this->validateApprovalEvidence(
                subject: "{$policy->title} version {$versionNumber}",
                approval: $approval,
                evidenceKeys: $evidenceKeys,
                evidenceGaps: $evidenceGaps,
            );
        }

        if ($status === PolicyLifecycleStatus::Effective && empty($version['effective_at'])) {
            $lifecycleGaps[] = $this->issue(
                'missing_effective_date',
                "{$policy->title} version {$versionNumber} is Effective without an effective date.",
            );
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

        return [
            ...$version,
            'status_label' => $status?->label() ?? 'Invalid',
            'content_integrity' => $contentIntegrity,
        ];
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
