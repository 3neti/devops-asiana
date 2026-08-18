<?php

namespace App\RoleTransitions;

use App\IdentityAndRoles\IdentityAndRoleDefinition;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveRoleTransitions
{
    /** @var array<string, string> */
    private const array TRANSITION_STATUSES = [
        'suspended' => 'suspended',
        'resigned' => 'ended',
        'removed' => 'ended',
        'revoked' => 'revoked',
        'ended' => 'ended',
    ];

    public function handle(
        RoleTransitionDefinition $definition,
        IdentityAndRoleDefinition $identityAndRoles,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedRoleTransitions {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $transitionGaps */
        $transitionGaps = [];
        /** @var list<array{code: string, message: string}> $decisionGaps */
        $decisionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $resolvedAsOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $identities = $this->indexByKey($identityAndRoles->identities);
        $assignments = $this->indexByKey($identityAndRoles->assignments);
        $evidence = $this->evidenceIndex($definition->evidenceRecords, $resolvedAsOf, $conflicts, $evidenceGaps);
        $transitionRecords = [];
        $admissions = [];
        $scheduled = [];
        $recordKeys = [];
        $verifiedByAssignment = [];

        foreach ($definition->transitionRecords as $record) {
            $issueCount = $this->issueCount($conflicts, $transitionGaps, $decisionGaps, $evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $assignmentKey = (string) ($record['assignment_key'] ?? '');
            $assignment = $assignments[$assignmentKey] ?? null;
            if ($key === '' || in_array($key, $recordKeys, true)) {
                $conflicts[] = $this->issue('invalid_role_transition_key', 'A Role Transition Record has a missing or duplicate key.');
            }
            $recordKeys[] = $key;
            if ($assignment === null) {
                $conflicts[] = $this->issue('unknown_transition_assignment', "Role Transition {$key} refers to an unknown Role Assignment.");
            }
            $transitionType = (string) ($record['transition_type'] ?? '');
            if (! isset(self::TRANSITION_STATUSES[$transitionType])) {
                $transitionGaps[] = $this->issue('invalid_transition_type', "Role Transition {$key} has an unsupported transition type.");
            }
            if (($record['status'] ?? null) !== 'admitted') {
                $transitionGaps[] = $this->issue('transition_not_admitted', "Role Transition {$key} is not admitted.");
            }

            $snapshot = is_array($record['assignment_snapshot'] ?? null) ? $record['assignment_snapshot'] : [];
            if ($assignment !== null && (
                ($snapshot['assignment_key'] ?? null) !== $assignmentKey
                || ($snapshot['role_key'] ?? null) !== ($assignment['role_key'] ?? null)
                || ($snapshot['identity_key'] ?? null) !== ($assignment['identity_key'] ?? null)
                || ($snapshot['basis_reference'] ?? null) !== ($assignment['basis']['reference'] ?? null)
            )) {
                $conflicts[] = $this->issue('transition_snapshot_mismatch', "Role Transition {$key} contradicts its canonical assignment snapshot.");
            }

            $decision = is_array($record['decision'] ?? null) ? $record['decision'] : [];
            $decisionAt = $this->date($decision['decided_at'] ?? null);
            $decisionMaker = (string) ($decision['decided_by_identity_key'] ?? '');
            if (! isset($identities[$decisionMaker])
                || empty($decision['authority_basis'])
                || ($decision['outcome'] ?? null) !== 'approved'
                || $decisionAt === null) {
                $decisionGaps[] = $this->issue('incomplete_transition_decision', "Role Transition {$key} lacks an attributable competent decision.");
            }
            if (! $this->hasEvidence($decision['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_transition_decision_evidence', "Role Transition {$key} lacks decision Evidence.");
            }

            $transition = is_array($record['transition'] ?? null) ? $record['transition'] : [];
            $effectiveAt = $this->date($transition['effective_at'] ?? null);
            $recordedAt = $this->date($transition['recorded_at'] ?? null);
            $holderKey = (string) ($assignment['identity_key'] ?? '');
            if (! isset($identities[$transition['recorded_by_identity_key'] ?? ''])
                || $effectiveAt === null
                || $recordedAt === null
                || ($decisionAt !== null && $effectiveAt->isBefore($decisionAt))
                || $effectiveAt->isAfter($recordedAt)
                || $recordedAt->isAfter($resolvedAsOf)) {
                $transitionGaps[] = $this->issue('invalid_transition_chronology', "Role Transition {$key} has incomplete attribution or invalid decision, effective, and recording chronology.");
            }
            if (! $this->hasEvidence($transition['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_transition_evidence', "Role Transition {$key} lacks transition Evidence.");
            }

            $verification = is_array($record['verification'] ?? null) ? $record['verification'] : [];
            $verifierKey = (string) ($verification['identity_key'] ?? '');
            $verifiedAt = $this->date($verification['verified_at'] ?? null);
            if (! isset($identities[$verifierKey])
                || $verifierKey === $holderKey
                || ($verification['outcome'] ?? null) !== 'confirmed'
                || $verifiedAt === null
                || ($decisionAt !== null && $verifiedAt->isBefore($decisionAt))
                || ($recordedAt !== null && $verifiedAt->isAfter($recordedAt))) {
                $decisionGaps[] = $this->issue('invalid_transition_verification', "Role Transition {$key} lacks independent verification by another recognized person.");
            }
            if (! $this->hasEvidence($verification['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_transition_verification_evidence', "Role Transition {$key} lacks verification Evidence.");
            }

            $evidenceKeys = [
                $decision['evidence_record_key'] ?? null,
                $transition['evidence_record_key'] ?? null,
                $verification['evidence_record_key'] ?? null,
            ];
            if (count(array_unique($evidenceKeys, SORT_REGULAR)) !== 3) {
                $conflicts[] = $this->issue('transition_evidence_not_separate', "Role Transition {$key} must preserve separate decision, transition, and verification Evidence.");
            }

            $successor = is_array($record['successor'] ?? null) ? $record['successor'] : null;
            $successorStatus = 'no_successor_recorded';
            if ($successor !== null) {
                $successorKey = (string) ($successor['assignment_key'] ?? '');
                $successorAssignment = $assignments[$successorKey] ?? null;
                if ($successorAssignment === null
                    || ($assignment !== null && ($successorAssignment['role_key'] ?? null) !== ($assignment['role_key'] ?? null))
                    || $successorKey === $assignmentKey) {
                    $conflicts[] = $this->issue('invalid_transition_successor', "Role Transition {$key} does not name a distinct assignment for the same Role as successor.");
                } else {
                    $successorStatus = 'pending_separate_admission';
                    $transitionGaps[] = $this->issue('successor_requires_separate_admission', "Role Transition {$key} records a possible successor, but successor appointment and activation remain separate admissions.");
                }
            }

            $verified = $this->issueCount($conflicts, $transitionGaps, $decisionGaps, $evidenceGaps) === $issueCount;
            $resolvedRecord = [
                ...$record,
                'transition_verified' => $verified,
                'effective_lifecycle_status' => self::TRANSITION_STATUSES[$transitionType] ?? null,
                'successor_status' => $successorStatus,
            ];
            $transitionRecords[] = $resolvedRecord;
            if ($verified && $assignment !== null && $effectiveAt !== null) {
                $admission = [
                    'key' => $key.'::'.$assignmentKey,
                    'source_type' => 'role_transition',
                    'transition_record_key' => $key,
                    'assignment_key' => $assignmentKey,
                    'transition_type' => $transitionType,
                    'effective_lifecycle_status' => self::TRANSITION_STATUSES[$transitionType],
                    'effective_at' => $effectiveAt->toIso8601String(),
                    'successor_status' => $successorStatus,
                    'grants_firm_authority' => false,
                ];
                if ($effectiveAt->isAfter($resolvedAsOf)) {
                    $scheduled[] = $admission;
                } else {
                    $admissions[] = $admission;
                    $verifiedByAssignment[$assignmentKey][] = $admission;
                }
            }
        }

        $currentAdmissions = [];
        foreach ($verifiedByAssignment as $assignmentKey => $assignmentAdmissions) {
            usort($assignmentAdmissions, static fn (array $left, array $right): int => strcmp($left['effective_at'], $right['effective_at']));
            $currentAdmissions[$assignmentKey] = end($assignmentAdmissions);
        }

        $vacancies = [];
        foreach ($currentAdmissions as $assignmentKey => $admission) {
            if (! in_array($admission['effective_lifecycle_status'], ['ended', 'revoked'], true)) {
                continue;
            }
            $vacancies[] = [
                'key' => $assignmentKey.'::vacancy',
                'assignment_key' => $assignmentKey,
                'role_key' => $assignments[$assignmentKey]['role_key'] ?? null,
                'outgoing_identity_key' => $assignments[$assignmentKey]['identity_key'] ?? null,
                'effective_at' => $admission['effective_at'],
                'successor_status' => $admission['successor_status'],
                'requires_separate_successor_admission' => true,
            ];
        }

        return new ResolvedRoleTransitions(
            $definition->schemaVersion,
            $definition->requirements,
            $transitionRecords,
            array_values($currentAdmissions),
            $scheduled,
            $vacancies,
            $definition->evidenceRecords,
            $conflicts,
            $transitionGaps,
            $decisionGaps,
            $evidenceGaps,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, array<string, mixed>>
     */
    private function evidenceIndex(array $records, Carbon $asOf, array &$conflicts, array &$evidenceGaps): array
    {
        $index = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || isset($index[$key])) {
                $conflicts[] = $this->issue('invalid_role_transition_evidence_key', 'A Role Transition Evidence Record has a missing or duplicate key.');
            }
            foreach (['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state'] as $field) {
                if (empty($record[$field])) {
                    $evidenceGaps[] = $this->issue('incomplete_role_transition_evidence', "Role Transition Evidence {$key} is incomplete.");
                    break;
                }
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf)) {
                $evidenceGaps[] = $this->issue('invalid_role_transition_evidence_time', "Role Transition Evidence {$key} is undated or future-dated.");
            }
            $index[$key] = $record;
        }

        return $index;
    }

    /** @param array<string, array<string, mixed>> $evidence */
    private function hasEvidence(mixed $key, array $evidence): bool
    {
        return is_string($key) && isset($evidence[$key]);
    }

    /** @param list<array{code: string, message: string}> ...$reports */
    private function issueCount(array ...$reports): int
    {
        return array_sum(array_map('count', $reports));
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
