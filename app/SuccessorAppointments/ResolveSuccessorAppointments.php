<?php

namespace App\SuccessorAppointments;

use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\Partnership\ResolvedPartnership;
use App\RoleTransitions\ResolvedRoleTransitions;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveSuccessorAppointments
{
    public function handle(
        SuccessorAppointmentDefinition $definition,
        IdentityAndRoleDefinition $identityAndRoles,
        ResolvedPartnership $partnership,
        ResolvedRoleTransitions $roleTransitions,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedSuccessorAppointments {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $appointmentGaps */
        $appointmentGaps = [];
        /** @var list<array{code: string, message: string}> $approvalGaps */
        $approvalGaps = [];
        /** @var list<array{code: string, message: string}> $acceptanceGaps */
        $acceptanceGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $resolvedAsOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $identities = $this->indexByKey($identityAndRoles->identities);
        $roles = $this->indexByKey($identityAndRoles->roles);
        $assignments = $this->indexByKey($identityAndRoles->assignments);
        $partners = $this->indexByKey($partnership->formation['founding_partners'] ?? []);
        foreach ($identities as $identityKey => &$identity) {
            $identity['partner_status'] = $partners[$identity['partnership_person_key'] ?? $identityKey]['partner_status'] ?? null;
        }
        unset($identity);
        $vacancies = $this->indexVacancies($roleTransitions);
        $evidence = $this->evidenceIndex($definition->evidenceRecords, $resolvedAsOf, $conflicts, $evidenceGaps);
        $records = [];
        $assignmentAdmissions = [];
        $activationAdmissions = [];
        $coverageOverrides = [];
        $recordKeys = [];
        $assignmentKeys = array_keys($assignments);

        foreach ($definition->appointmentRecords as $record) {
            $issueCount = $this->issueCount($conflicts, $appointmentGaps, $approvalGaps, $acceptanceGaps, $evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $assignmentKey = (string) ($record['assignment_key'] ?? '');
            $roleKey = (string) ($record['role_key'] ?? '');
            $identityKey = (string) ($record['successor_identity_key'] ?? '');
            $role = $roles[$roleKey] ?? null;
            $identity = $identities[$identityKey] ?? null;
            if ($key === '' || in_array($key, $recordKeys, true)) {
                $conflicts[] = $this->issue('invalid_successor_appointment_key', 'A Successor Appointment Record has a missing or duplicate key.');
            }
            $recordKeys[] = $key;
            if ($assignmentKey === '' || in_array($assignmentKey, $assignmentKeys, true)) {
                $conflicts[] = $this->issue('duplicate_successor_assignment_key', "Successor Appointment {$key} has a missing or colliding assignment key.");
            }
            $assignmentKeys[] = $assignmentKey;
            if ($role === null) {
                $conflicts[] = $this->issue('unknown_successor_role', "Successor Appointment {$key} refers to an unknown Role.");
            }
            if ($identity === null) {
                $conflicts[] = $this->issue('unknown_successor_identity', "Successor Appointment {$key} refers to an unknown Institutional Identity.");
            }
            if (($record['status'] ?? null) !== 'admitted') {
                $appointmentGaps[] = $this->issue('successor_appointment_not_admitted', "Successor Appointment {$key} is not admitted.");
            }

            $predecessorKey = (string) ($record['predecessor_assignment_key'] ?? '');
            $vacancy = $vacancies[$predecessorKey] ?? null;
            if ($vacancy === null) {
                $appointmentGaps[] = $this->issue('successor_requires_effective_vacancy', "Successor Appointment {$key} does not reference an effective predecessor vacancy.");
            } elseif (($vacancy['role_key'] ?? null) !== $roleKey) {
                $conflicts[] = $this->issue('successor_role_mismatch', "Successor Appointment {$key} does not appoint a successor to the predecessor's Role.");
            }

            $snapshot = is_array($record['assignment_snapshot'] ?? null) ? $record['assignment_snapshot'] : [];
            if (($snapshot['assignment_key'] ?? null) !== $assignmentKey
                || ($snapshot['role_key'] ?? null) !== $roleKey
                || ($snapshot['identity_key'] ?? null) !== $identityKey
                || empty($snapshot['basis_reference'])) {
                $conflicts[] = $this->issue('successor_assignment_snapshot_incomplete', "Successor Appointment {$key} lacks an exact new assignment snapshot.");
            }

            $qualified = $role !== null && $identity !== null && in_array($identity['partner_status'] ?? null, $role['qualified_partner_statuses'] ?? [], true);
            if (! $qualified) {
                $appointmentGaps[] = $this->issue('unqualified_successor', "Successor Appointment {$key} does not satisfy the Role qualification independently.");
            }

            $appointment = is_array($record['appointment'] ?? null) ? $record['appointment'] : [];
            $decidedAt = $this->date($appointment['decided_at'] ?? null);
            if (! isset($identities[$appointment['decided_by_identity_key'] ?? ''])
                || empty($appointment['authority_basis'])
                || ($appointment['outcome'] ?? null) !== 'approved'
                || $decidedAt === null) {
                $approvalGaps[] = $this->issue('incomplete_successor_appointment_approval', "Successor Appointment {$key} lacks an attributable approval.");
            }
            if (! $this->hasEvidence($appointment['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_successor_appointment_approval_evidence', "Successor Appointment {$key} lacks approval Evidence.");
            }

            $acceptance = is_array($record['holder_acceptance'] ?? null) ? $record['holder_acceptance'] : [];
            $acceptedAt = $this->date($acceptance['accepted_at'] ?? null);
            if (($acceptance['identity_key'] ?? null) !== $identityKey
                || ($acceptance['decision'] ?? null) !== 'accept'
                || $acceptedAt === null) {
                $acceptanceGaps[] = $this->issue('invalid_successor_holder_acceptance', "Successor Appointment {$key} lacks the proposed holder's acceptance.");
            }
            if (! $this->hasEvidence($acceptance['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_successor_holder_acceptance_evidence', "Successor Appointment {$key} lacks holder-acceptance Evidence.");
            }

            $activation = is_array($record['activation'] ?? null) ? $record['activation'] : [];
            $effectiveAt = $this->date($activation['effective_at'] ?? null);
            $recordedAt = $this->date($activation['recorded_at'] ?? null);
            $verification = is_array($record['verification'] ?? null) ? $record['verification'] : [];
            $verifiedAt = $this->date($verification['verified_at'] ?? null);
            $verifierKey = (string) ($verification['identity_key'] ?? '');
            $vacancyEffectiveAt = $this->date($vacancy['effective_at'] ?? null);
            if (! isset($identities[$activation['recorded_by_identity_key'] ?? ''])
                || $effectiveAt === null
                || $recordedAt === null
                || $recordedAt->isAfter($resolvedAsOf)
                || $effectiveAt->isAfter($recordedAt)
                || ($vacancyEffectiveAt !== null && $effectiveAt->isBefore($vacancyEffectiveAt))
                || ($decidedAt !== null && $effectiveAt->isBefore($decidedAt))
                || ($acceptedAt !== null && $effectiveAt->isBefore($acceptedAt))
                || ($verifiedAt !== null && $effectiveAt->isBefore($verifiedAt))) {
                $appointmentGaps[] = $this->issue('invalid_successor_activation_chronology', "Successor Appointment {$key} has invalid approval, vacancy, acceptance, verification, effective, or recording chronology.");
            }
            if (! isset($identities[$verifierKey])
                || $verifierKey === $identityKey
                || ($verification['outcome'] ?? null) !== 'confirmed'
                || $verifiedAt === null) {
                $approvalGaps[] = $this->issue('invalid_successor_independent_verification', "Successor Appointment {$key} lacks independent verification by another recognized person.");
            }
            if (! $this->hasEvidence($activation['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_successor_activation_evidence', "Successor Appointment {$key} lacks activation Evidence.");
            }
            if (! $this->hasEvidence($verification['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_successor_verification_evidence', "Successor Appointment {$key} lacks verification Evidence.");
            }

            $evidenceKeys = [
                $appointment['evidence_record_key'] ?? null,
                $acceptance['evidence_record_key'] ?? null,
                $activation['evidence_record_key'] ?? null,
                $verification['evidence_record_key'] ?? null,
            ];
            if (count(array_unique($evidenceKeys, SORT_REGULAR)) !== 4) {
                $conflicts[] = $this->issue('successor_evidence_not_separate', "Successor Appointment {$key} must preserve separate approval, acceptance, activation, and verification Evidence.");
            }

            $verified = $this->issueCount($conflicts, $appointmentGaps, $approvalGaps, $acceptanceGaps, $evidenceGaps) === $issueCount;
            $resolvedRecord = [...$record, 'appointment_verified' => $verified];
            $records[] = $resolvedRecord;
            if (! $verified || $role === null || $identity === null || $effectiveAt === null) {
                continue;
            }

            $assignmentAdmissions[] = [
                'key' => $assignmentKey,
                'role_key' => $roleKey,
                'identity_key' => $identityKey,
                'lifecycle_status' => 'approved',
                'basis' => ['type' => 'appointment', 'reference' => $snapshot['basis_reference']],
                'effective_at' => null,
                'effective_at_source' => null,
                'expires_at' => null,
                'authority_scope' => null,
                'approval' => [
                    'approver' => $appointment['decided_by_identity_key'],
                    'authority_basis' => $appointment['authority_basis'],
                    'outcome' => $appointment['outcome'],
                    'decided_at' => $appointment['decided_at'],
                    'evidence_record_key' => $appointment['evidence_record_key'],
                ],
                'evidence_record_key' => $activation['evidence_record_key'],
                'disposition' => null,
                'source_type' => 'successor_appointment',
                'predecessor_assignment_key' => $predecessorKey,
            ];
            $activationAdmissions[] = [
                'key' => $key.'::'.$assignmentKey,
                'source_type' => 'successor_appointment',
                'activation_record_key' => $key,
                'assignment_key' => $assignmentKey,
                'role_key' => $roleKey,
                'identity_key' => $identityKey,
                'effective_at' => $effectiveAt->toIso8601String(),
                'activates_exact_assignment' => true,
                'grants_firm_authority' => false,
                'authority_effect' => ($role['category'] ?? null) === 'office' ? 'eligible_for_separate_authority_resolution' : 'none',
            ];
            $coverageOverrides[$roleKey] = [$identityKey];
        }

        return new ResolvedSuccessorAppointments(
            $definition->schemaVersion,
            $definition->requirements,
            $records,
            $assignmentAdmissions,
            $activationAdmissions,
            $coverageOverrides,
            $definition->evidenceRecords,
            $conflicts,
            $appointmentGaps,
            $approvalGaps,
            $acceptanceGaps,
            $evidenceGaps,
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function indexVacancies(ResolvedRoleTransitions $transitions): array
    {
        $indexed = [];
        foreach ($transitions->vacancies as $vacancy) {
            if (is_string($vacancy['assignment_key'] ?? null)) {
                $indexed[$vacancy['assignment_key']] = $vacancy;
            }
        }

        return $indexed;
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
                $conflicts[] = $this->issue('invalid_successor_evidence_key', 'A Successor Appointment Evidence Record has a missing or duplicate key.');
            }
            foreach (['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state'] as $field) {
                if (empty($record[$field])) {
                    $evidenceGaps[] = $this->issue('incomplete_successor_evidence', "Successor Appointment Evidence {$key} is incomplete.");
                    break;
                }
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf)) {
                $evidenceGaps[] = $this->issue('invalid_successor_evidence_time', "Successor Appointment Evidence {$key} is undated or future-dated.");
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
