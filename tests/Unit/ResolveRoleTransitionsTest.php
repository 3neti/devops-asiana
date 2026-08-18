<?php

use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\RoleTransitions\ResolveRoleTransitions;
use App\RoleTransitions\RoleTransitionDefinition;
use DateTimeImmutable;

function roleTransitionIdentityDefinition(bool $withSuccessor = false): IdentityAndRoleDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/identity-and-roles.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if ($withSuccessor) {
        $definition['assignments'][] = [
            'key' => 'client-delivery-lester-successor',
            'role_key' => 'client-delivery',
            'identity_key' => 'lester-hurtado',
            'lifecycle_status' => 'approved',
            'basis' => ['type' => 'appointment', 'reference' => 'future.client-delivery-appointment'],
            'effective_at' => '2026-08-03T00:00:00+08:00',
            'effective_at_source' => null,
            'expires_at' => null,
            'authority_scope' => null,
            'approval' => [
                'approver' => 'angelica-santos',
                'authority_basis' => 'partnership.constitution.assignment-appointment',
                'outcome' => 'approved',
                'decided_at' => '2026-08-02T08:00:00+08:00',
                'evidence_record_key' => 'future-appointment-evidence',
            ],
            'evidence_record_key' => null,
            'disposition' => null,
        ];
    }

    return IdentityAndRoleDefinition::fromArray($definition);
}

/** @return array<string, mixed> */
function roleTransitionEvidence(string $key, string $subject): array
{
    return [
        'key' => $key,
        'record_type' => 'Role Transition Evidence',
        'subject' => $subject,
        'actor' => 'Founding Partners',
        'recorded_at' => '2026-08-02T12:00:00+08:00',
        'source' => 'Role transition record',
        'reason' => 'Preserve an attributable assignment lifecycle change.',
        'state' => 'accepted',
    ];
}

/** @return array<string, mixed> */
function endingTransitionRecord(): array
{
    return [
        'key' => 'transition-client-delivery-angelica-001',
        'status' => 'admitted',
        'transition_type' => 'resigned',
        'assignment_key' => 'client-delivery-angelica',
        'assignment_snapshot' => [
            'assignment_key' => 'client-delivery-angelica',
            'role_key' => 'client-delivery',
            'identity_key' => 'angelica-santos',
            'basis_reference' => 'constitution.responsibility_assignments.client-delivery',
        ],
        'decision' => [
            'decided_by_identity_key' => 'lester-hurtado',
            'authority_basis' => 'partnership.constitution.assignment-disposition',
            'outcome' => 'approved',
            'decided_at' => '2026-08-02T09:00:00+08:00',
            'evidence_record_key' => 'evidence-transition-decision',
        ],
        'transition' => [
            'effective_at' => '2026-08-02T10:00:00+08:00',
            'recorded_at' => '2026-08-02T12:00:00+08:00',
            'recorded_by_identity_key' => 'lester-hurtado',
            'evidence_record_key' => 'evidence-transition-effective',
        ],
        'verification' => [
            'identity_key' => 'lester-hurtado',
            'outcome' => 'confirmed',
            'verified_at' => '2026-08-02T11:00:00+08:00',
            'evidence_record_key' => 'evidence-transition-verification',
        ],
        'successor' => null,
    ];
}

/** @param list<array<string, mixed>> $records */
function resolveRoleTransitions(array $records = [], bool $withSuccessor = false): array
{
    $evidence = $records === [] ? [] : [
        roleTransitionEvidence('evidence-transition-decision', 'Competent transition decision'),
        roleTransitionEvidence('evidence-transition-effective', 'Effective transition recording'),
        roleTransitionEvidence('evidence-transition-verification', 'Independent transition verification'),
    ];

    return (new ResolveRoleTransitions)->handle(
        new RoleTransitionDefinition(1, [], $records, $evidence),
        roleTransitionIdentityDefinition($withSuccessor),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('the empty transition register does not invent lifecycle changes', function () {
    $resolved = resolveRoleTransitions();

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['transition_records'])->toBe(0)
        ->and($resolved['counts']['effective_transitions'])->toBe(0)
        ->and($resolved['counts']['vacancies'])->toBe(0);
});

test('an evidenced resignation ends one assignment and exposes a vacancy', function () {
    $resolved = resolveRoleTransitions([endingTransitionRecord()]);
    $admission = $resolved['assignment_transition_admissions'][0];
    $vacancy = $resolved['vacancies'][0];

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($admission['assignment_key'])->toBe('client-delivery-angelica')
        ->and($admission['effective_lifecycle_status'])->toBe('ended')
        ->and($admission['grants_firm_authority'])->toBeFalse()
        ->and($vacancy['role_key'])->toBe('client-delivery')
        ->and($vacancy['requires_separate_successor_admission'])->toBeTrue()
        ->and($vacancy['successor_status'])->toBe('no_successor_recorded');
});

test('a transition snapshot mismatch cannot be admitted', function () {
    $record = endingTransitionRecord();
    $record['assignment_snapshot']['identity_key'] = 'lester-hurtado';
    $resolved = resolveRoleTransitions([$record]);

    expect($resolved['assignment_transition_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('transition_snapshot_mismatch');
});

test('a successor declaration remains pending a separate admission', function () {
    $record = endingTransitionRecord();
    $record['successor'] = ['assignment_key' => 'client-delivery-lester-successor'];
    $resolved = resolveRoleTransitions([$record], true);

    expect($resolved['assignment_transition_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['transition_gaps'], 'code'))
        ->toContain('successor_requires_separate_admission');
});
