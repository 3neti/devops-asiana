<?php

namespace App\RoleActivations;

use App\FormationCompletion\ResolvedFormationCompletion;
use App\IdentityAndRoles\IdentityAndRoleDefinition;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveRoleActivations
{
    public function handle(
        RoleActivationDefinition $definition,
        IdentityAndRoleDefinition $identityAndRoles,
        ResolvedFormationCompletion $formationCompletion,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedRoleActivations {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $activationGaps */
        $activationGaps = [];
        /** @var list<array{code: string, message: string}> $acceptanceGaps */
        $acceptanceGaps = [];
        /** @var list<array{code: string, message: string}> $verificationGaps */
        $verificationGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $resolvedAsOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $identities = $this->indexByKey($identityAndRoles->identities);
        $roles = $this->indexByKey($identityAndRoles->roles);
        $assignments = $this->indexByKey($identityAndRoles->assignments);
        $commencementBases = $this->commencementBasisIndex($formationCompletion);
        $candidates = array_map(
            static fn (array $candidate): array => [...$candidate, 'commencement_eligible' => $commencementBases !== []],
            $this->candidates($identityAndRoles->assignments, $identities, $roles),
        );
        $candidateIndex = $this->indexByKey($candidates);
        $evidence = $this->evidenceIndex($definition->evidenceRecords, $resolvedAsOf, $conflicts, $evidenceGaps);

        if ($commencementBases === []) {
            $activationGaps[] = $this->issue(
                'verified_firm_commencement_unavailable',
                'No effective verified Firm Commencement basis is available for formation-derived assignment activation.',
            );
        }

        $records = [];
        $admissions = [];
        $recordKeys = [];
        $recordedAssignmentKeys = [];
        foreach ($definition->activationRecords as $record) {
            $issueCount = $this->issueCount($conflicts, $activationGaps, $acceptanceGaps, $verificationGaps, $evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $assignmentKey = (string) ($record['assignment_key'] ?? '');
            $candidate = $candidateIndex[$assignmentKey] ?? null;
            $assignment = $assignments[$assignmentKey] ?? null;

            if ($key === '' || in_array($key, $recordKeys, true)) {
                $conflicts[] = $this->issue('invalid_role_activation_key', 'A Role Activation Record has a missing or duplicate key.');
            }
            $recordKeys[] = $key;
            if ($assignmentKey === '' || in_array($assignmentKey, $recordedAssignmentKeys, true)) {
                $conflicts[] = $this->issue('duplicate_assignment_activation', "Role Activation {$key} has a missing or duplicate assignment target.");
            }
            $recordedAssignmentKeys[] = $assignmentKey;
            if ($candidate === null || $assignment === null) {
                $conflicts[] = $this->issue('ineligible_assignment_activation', "Role Activation {$key} does not target an exact approved formation-derived assignment.");
            }
            if (($record['status'] ?? null) !== 'assumed') {
                $activationGaps[] = $this->issue('role_assumption_incomplete', "Role Activation {$key} is not recorded as Assumed.");
            }

            $snapshot = is_array($record['assignment_snapshot'] ?? null) ? $record['assignment_snapshot'] : [];
            if ($assignment !== null && (
                ($snapshot['assignment_key'] ?? null) !== $assignmentKey
                || ($snapshot['role_key'] ?? null) !== ($assignment['role_key'] ?? null)
                || ($snapshot['identity_key'] ?? null) !== ($assignment['identity_key'] ?? null)
                || ($snapshot['basis_reference'] ?? null) !== ($assignment['basis']['reference'] ?? null)
            )) {
                $conflicts[] = $this->issue('role_activation_snapshot_mismatch', "Role Activation {$key} contradicts its canonical assignment snapshot.");
            }

            $basisKey = (string) ($record['commencement_basis_key'] ?? '');
            $commencementBasis = $commencementBases[$basisKey] ?? null;
            if ($commencementBasis === null) {
                $activationGaps[] = $this->issue('invalid_role_activation_commencement_basis', "Role Activation {$key} lacks an exact effective Firm Commencement basis.");
            }

            $acceptance = is_array($record['holder_acceptance'] ?? null) ? $record['holder_acceptance'] : [];
            $holderIdentityKey = (string) ($assignment['identity_key'] ?? '');
            $acceptedAt = $this->date($acceptance['accepted_at'] ?? null);
            if (($acceptance['identity_key'] ?? null) !== $holderIdentityKey
                || ($acceptance['decision'] ?? null) !== 'accept'
                || $acceptedAt === null) {
                $acceptanceGaps[] = $this->issue('invalid_holder_acceptance', "Role Activation {$key} lacks the exact holder's attributable acceptance.");
            }
            if (! $this->hasEvidence($acceptance['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_holder_acceptance_evidence', "Role Activation {$key} lacks holder-acceptance Evidence.");
            }

            $verification = is_array($record['independent_verification'] ?? null) ? $record['independent_verification'] : [];
            $verifierIdentityKey = (string) ($verification['identity_key'] ?? '');
            $verifiedAt = $this->date($verification['verified_at'] ?? null);
            if (! isset($identities[$verifierIdentityKey])
                || $verifierIdentityKey === $holderIdentityKey
                || ($verification['outcome'] ?? null) !== 'confirmed'
                || $verifiedAt === null) {
                $verificationGaps[] = $this->issue('invalid_independent_verification', "Role Activation {$key} lacks independent verification by another recognized person.");
            }
            if (! $this->hasEvidence($verification['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_role_activation_verification_evidence', "Role Activation {$key} lacks independent-verification Evidence.");
            }

            $activation = is_array($record['activation'] ?? null) ? $record['activation'] : [];
            $effectiveAt = $this->date($activation['effective_at'] ?? null);
            $recordedAt = $this->date($activation['recorded_at'] ?? null);
            $commencementEffectiveAt = $this->date($commencementBasis['effective_at'] ?? null);
            if (! isset($identities[$activation['recorded_by_identity_key'] ?? ''])
                || $effectiveAt === null
                || $recordedAt === null
                || $effectiveAt->isAfter($recordedAt)
                || $recordedAt->isAfter($resolvedAsOf)
                || ($commencementEffectiveAt !== null && $effectiveAt->isBefore($commencementEffectiveAt))
                || ($acceptedAt !== null && $effectiveAt->isBefore($acceptedAt))
                || ($verifiedAt !== null && ($verifiedAt->isBefore($acceptedAt ?? $verifiedAt) || $effectiveAt->isBefore($verifiedAt)))) {
                $activationGaps[] = $this->issue('invalid_role_activation_chronology', "Role Activation {$key} has incomplete attribution or invalid commencement, acceptance, verification, effective, and recording chronology.");
            }
            if (! $this->hasEvidence($activation['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_role_activation_evidence', "Role Activation {$key} lacks separate activation Evidence.");
            }

            $evidenceKeys = [
                $acceptance['evidence_record_key'] ?? null,
                $verification['evidence_record_key'] ?? null,
                $activation['evidence_record_key'] ?? null,
            ];
            if (count(array_unique($evidenceKeys, SORT_REGULAR)) !== 3) {
                $conflicts[] = $this->issue('role_activation_evidence_not_separate', "Role Activation {$key} must preserve separate Evidence for acceptance, verification, and activation.");
            }

            $verified = $this->issueCount($conflicts, $activationGaps, $acceptanceGaps, $verificationGaps, $evidenceGaps) === $issueCount;
            $records[] = [...$record, 'activation_verified' => $verified];
            if ($verified && $candidate !== null && $effectiveAt !== null) {
                $admissions[] = [
                    'key' => $key.'::'.$assignmentKey,
                    'source_type' => 'role_assumption',
                    'activation_record_key' => $key,
                    'assignment_key' => $assignmentKey,
                    'role_key' => $candidate['role_key'],
                    'identity_key' => $candidate['identity_key'],
                    'effective_at' => $effectiveAt->toIso8601String(),
                    'holder_acceptance_evidence_record_key' => $acceptance['evidence_record_key'],
                    'verification_evidence_record_key' => $verification['evidence_record_key'],
                    'activation_evidence_record_key' => $activation['evidence_record_key'],
                    'activates_exact_assignment' => true,
                    'grants_firm_authority' => false,
                    'authority_effect' => ($candidate['role_category'] ?? null) === 'office'
                        ? 'eligible_for_separate_authority_resolution'
                        : 'none',
                ];
            }
        }

        foreach ($candidates as $candidate) {
            if (! in_array($candidate['key'], $recordedAssignmentKeys, true)) {
                $activationGaps[] = $this->issue(
                    'formation_assignment_assumption_not_recorded',
                    "Formation assignment {$candidate['key']} has no holder-assumption record.",
                );
            }
        }

        if ($conflicts !== [] || $acceptanceGaps !== [] || $verificationGaps !== [] || $evidenceGaps !== []) {
            $admissions = [];
        }

        return new ResolvedRoleActivations(
            $definition->schemaVersion,
            $definition->requirements,
            $candidates,
            $records,
            $admissions,
            $definition->evidenceRecords,
            $conflicts,
            $activationGaps,
            $acceptanceGaps,
            $verificationGaps,
            $evidenceGaps,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  array<string, array<string, mixed>>  $identities
     * @param  array<string, array<string, mixed>>  $roles
     * @return list<array<string, mixed>>
     */
    private function candidates(array $assignments, array $identities, array $roles): array
    {
        $candidates = [];
        foreach ($assignments as $assignment) {
            if (($assignment['basis']['type'] ?? null) !== 'formation' || ($assignment['lifecycle_status'] ?? null) !== 'approved') {
                continue;
            }
            $identity = $identities[$assignment['identity_key'] ?? ''] ?? [];
            $role = $roles[$assignment['role_key'] ?? ''] ?? [];
            $candidates[] = [
                'key' => $assignment['key'],
                'role_key' => $assignment['role_key'],
                'role_name' => $role['name'] ?? $assignment['role_key'],
                'role_category' => $role['category'] ?? null,
                'authority_attachment' => $role['authority_attachment'] ?? null,
                'identity_key' => $assignment['identity_key'],
                'identity_name' => $identity['partnership_person_key'] ?? $assignment['identity_key'],
                'basis_reference' => $assignment['basis']['reference'],
                'recorded_lifecycle_status' => $assignment['lifecycle_status'],
            ];
        }

        return $candidates;
    }

    /** @return array<string, array<string, mixed>> */
    private function commencementBasisIndex(ResolvedFormationCompletion $formationCompletion): array
    {
        $index = [];
        foreach ($formationCompletion->officeActivationBases as $basis) {
            if (($basis['permits_formation_derived_assignments'] ?? false) === true && is_string($basis['key'] ?? null)) {
                $index[$basis['key']] = $basis;
            }
        }

        return $index;
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
                $conflicts[] = $this->issue('invalid_role_activation_evidence_key', 'A Role Activation Evidence Record has a missing or duplicate key.');
            }
            foreach (['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state'] as $field) {
                if (empty($record[$field])) {
                    $evidenceGaps[] = $this->issue('incomplete_role_activation_evidence', "Role Activation Evidence {$key} is incomplete.");
                    break;
                }
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf)) {
                $evidenceGaps[] = $this->issue('invalid_role_activation_evidence_time', "Role Activation Evidence {$key} is undated or future-dated.");
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
        $index = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $index[$record['key']] = $record;
            }
        }

        return $index;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
