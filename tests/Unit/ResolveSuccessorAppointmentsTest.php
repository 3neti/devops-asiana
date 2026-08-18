<?php

use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvedPartnership;
use App\Partnership\ResolvePartnership;
use App\RoleTransitions\ResolvedRoleTransitions;
use App\SuccessorAppointments\ResolveSuccessorAppointments;
use App\SuccessorAppointments\SuccessorAppointmentDefinition;
use DateTimeImmutable;

function successorDefinition(array $records = [], array $evidence = []): SuccessorAppointmentDefinition
{
    return new SuccessorAppointmentDefinition(1, [], $records, $evidence);
}

function successorIdentityDefinition(): IdentityAndRoleDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/identity-and-roles.json'), true, flags: JSON_THROW_ON_ERROR);

    return IdentityAndRoleDefinition::fromArray($definition);
}

function successorPartnership(): ResolvedPartnership
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/partnership.json'), true, flags: JSON_THROW_ON_ERROR);

    return (new ResolvePartnership)->handle(PartnershipDefinition::fromArray($definition));
}

function successorVacancy(bool $present = true): ResolvedRoleTransitions
{
    return new ResolvedRoleTransitions(
        1,
        [],
        [],
        [],
        [],
        $present ? [[
            'key' => 'client-delivery-angelica::vacancy',
            'assignment_key' => 'client-delivery-angelica',
            'role_key' => 'client-delivery',
            'outgoing_identity_key' => 'angelica-santos',
            'effective_at' => '2026-08-02T10:00:00+08:00',
            'successor_status' => 'no_successor_recorded',
            'requires_separate_successor_admission' => true,
        ]] : [],
        [],
        [],
        [],
        [],
        [],
    );
}

function validSuccessorRecord(): array
{
    return [
        'key' => 'successor-client-delivery-lester-001',
        'status' => 'admitted',
        'role_key' => 'client-delivery',
        'assignment_key' => 'client-delivery-lester-successor',
        'successor_identity_key' => 'lester-hurtado',
        'predecessor_assignment_key' => 'client-delivery-angelica',
        'assignment_snapshot' => [
            'assignment_key' => 'client-delivery-lester-successor',
            'role_key' => 'client-delivery',
            'identity_key' => 'lester-hurtado',
            'basis_reference' => 'future.client-delivery.successor',
        ],
        'appointment' => [
            'decided_by_identity_key' => 'angelica-santos',
            'authority_basis' => 'partnership.constitution.assignment-appointment',
            'outcome' => 'approved',
            'decided_at' => '2026-08-03T09:00:00+08:00',
            'evidence_record_key' => 'successor-appointment-approval',
        ],
        'holder_acceptance' => [
            'identity_key' => 'lester-hurtado',
            'decision' => 'accept',
            'accepted_at' => '2026-08-03T09:30:00+08:00',
            'evidence_record_key' => 'successor-holder-acceptance',
        ],
        'verification' => [
            'identity_key' => 'angelica-santos',
            'outcome' => 'confirmed',
            'verified_at' => '2026-08-03T10:00:00+08:00',
            'evidence_record_key' => 'successor-independent-verification',
        ],
        'activation' => [
            'effective_at' => '2026-08-03T10:00:00+08:00',
            'recorded_at' => '2026-08-03T12:00:00+08:00',
            'recorded_by_identity_key' => 'angelica-santos',
            'evidence_record_key' => 'successor-activation',
        ],
    ];
}

function successorEvidence(): array
{
    return array_map(static fn (string $key): array => [
        'key' => $key,
        'record_type' => 'Successor Appointment Evidence',
        'subject' => 'successor-client-delivery-lester-001',
        'actor' => 'angelica-santos',
        'recorded_at' => '2026-08-03T12:00:00+08:00',
        'source' => 'Institutional appointment record',
        'reason' => 'Preserve successor admission evidence.',
        'state' => 'accepted',
    ], [
        'successor-appointment-approval',
        'successor-holder-acceptance',
        'successor-independent-verification',
        'successor-activation',
    ]);
}

function resolveSuccessor(array $records = [], array $evidence = [], ?ResolvedRoleTransitions $transitions = null): array
{
    return (new ResolveSuccessorAppointments)->handle(
        successorDefinition($records, $evidence),
        successorIdentityDefinition(),
        successorPartnership(),
        $transitions ?? successorVacancy(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('an empty successor register is consistent without inventing appointments', function () {
    $resolved = resolveSuccessor();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['assignment_admissions'])->toBe(0)
        ->and($resolved['counts']['activation_admissions'])->toBe(0);
});

test('a fully evidenced successor creates a new assignment and closes coverage through activation', function () {
    $resolved = resolveSuccessor([validSuccessorRecord()], successorEvidence());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['assignment_admissions'])->toHaveCount(1)
        ->and($resolved['activation_admissions'][0]['assignment_key'])->toBe('client-delivery-lester-successor')
        ->and($resolved['activation_admissions'][0]['grants_firm_authority'])->toBeFalse()
        ->and($resolved['coverage_holder_overrides']['client-delivery'])->toBe(['lester-hurtado']);
});

test('a successor cannot be admitted without an effective predecessor vacancy', function () {
    $resolved = resolveSuccessor([validSuccessorRecord()], successorEvidence(), successorVacancy(false));

    expect($resolved['assignment_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['appointment_gaps'], 'code'))
        ->toContain('successor_requires_effective_vacancy');
});

test('approval acceptance activation verification and evidence remain independent', function () {
    $record = validSuccessorRecord();
    $record['verification']['identity_key'] = 'lester-hurtado';
    $record['activation']['evidence_record_key'] = 'successor-appointment-approval';
    $resolved = resolveSuccessor([$record], successorEvidence());

    expect($resolved['assignment_admissions'])->toBeEmpty()
        ->and($resolved['reports']['conflicts'])->not->toBeEmpty()
        ->and(array_column($resolved['reports']['approval_gaps'], 'code'))
        ->toContain('invalid_successor_independent_verification');
});
