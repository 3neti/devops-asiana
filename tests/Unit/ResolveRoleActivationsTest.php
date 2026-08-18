<?php

use App\FormationCompletion\ResolvedFormationCompletion;
use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\RoleActivations\ResolveRoleActivations;
use App\RoleActivations\RoleActivationDefinition;
use DateTimeImmutable;

function roleActivationIdentityDefinition(): IdentityAndRoleDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/identity-and-roles.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return IdentityAndRoleDefinition::fromArray($definition);
}

function roleActivationFormationCompletion(): ResolvedFormationCompletion
{
    return new ResolvedFormationCompletion(
        schemaVersion: 1,
        requirements: [],
        legalRequirementsRule: [],
        capitalInitialization: [],
        commencementRecords: [],
        officeActivationBases: [[
            'key' => 'firm-commencement-001::formation-derived-assignments',
            'effective_at' => '2026-08-01T00:00:00+08:00',
            'permits_formation_derived_assignments' => true,
        ]],
        evidenceRecords: [],
        conflicts: [],
        formationGaps: [],
        legalGaps: [],
        capitalGaps: [],
        evidenceGaps: [],
        counselReview: [],
    );
}

function roleActivationUncommencedFormation(): ResolvedFormationCompletion
{
    return new ResolvedFormationCompletion(
        schemaVersion: 1,
        requirements: [],
        legalRequirementsRule: [],
        capitalInitialization: [],
        commencementRecords: [],
        officeActivationBases: [],
        evidenceRecords: [],
        conflicts: [],
        formationGaps: [],
        legalGaps: [],
        capitalGaps: [],
        evidenceGaps: [],
        counselReview: [],
    );
}

/** @return array<string, mixed> */
function roleActivationEvidence(string $key, string $subject): array
{
    return [
        'key' => $key,
        'record_type' => 'Role Activation Evidence',
        'subject' => $subject,
        'actor' => 'Founding Partners',
        'recorded_at' => '2026-08-02T10:00:00+08:00',
        'source' => 'Executed role assumption record',
        'reason' => 'Preserve an attributable role activation fact.',
        'state' => 'accepted',
    ];
}

/** @return array<string, mixed> */
function managingPartnerAssumption(): array
{
    return [
        'key' => 'assumption-managing-partner-angelica-001',
        'status' => 'assumed',
        'assignment_key' => 'managing-partner-angelica',
        'commencement_basis_key' => 'firm-commencement-001::formation-derived-assignments',
        'assignment_snapshot' => [
            'assignment_key' => 'managing-partner-angelica',
            'role_key' => 'managing-partner',
            'identity_key' => 'angelica-santos',
            'basis_reference' => 'formation.founding_partners.angelica-santos.offices.managing-partner',
        ],
        'holder_acceptance' => [
            'identity_key' => 'angelica-santos',
            'decision' => 'accept',
            'accepted_at' => '2026-08-02T09:00:00+08:00',
            'evidence_record_key' => 'evidence-managing-partner-acceptance',
        ],
        'independent_verification' => [
            'identity_key' => 'lester-hurtado',
            'outcome' => 'confirmed',
            'verified_at' => '2026-08-02T09:30:00+08:00',
            'evidence_record_key' => 'evidence-managing-partner-verification',
        ],
        'activation' => [
            'effective_at' => '2026-08-02T09:30:00+08:00',
            'recorded_at' => '2026-08-02T10:00:00+08:00',
            'recorded_by_identity_key' => 'lester-hurtado',
            'evidence_record_key' => 'evidence-managing-partner-activation',
        ],
    ];
}

/** @param list<array<string, mixed>> $records */
function resolveRoleActivations(array $records = [], ?ResolvedFormationCompletion $formation = null): array
{
    $evidence = $records === [] ? [] : [
        roleActivationEvidence('evidence-managing-partner-acceptance', 'Managing Partner holder acceptance'),
        roleActivationEvidence('evidence-managing-partner-verification', 'Managing Partner independent verification'),
        roleActivationEvidence('evidence-managing-partner-activation', 'Managing Partner activation'),
    ];

    return (new ResolveRoleActivations)->handle(
        new RoleActivationDefinition(1, [], $records, $evidence),
        roleActivationIdentityDefinition(),
        $formation ?? roleActivationFormationCompletion(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('commencement does not silently activate founding assignments', function () {
    $resolved = resolveRoleActivations();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['candidate_assignments'])->toBe(8)
        ->and($resolved['counts']['commencement_eligible_assignments'])->toBe(8)
        ->and($resolved['counts']['recorded_assumptions'])->toBe(0)
        ->and($resolved['counts']['admitted_activations'])->toBe(0)
        ->and($resolved['counts']['pending_assignments'])->toBe(8)
        ->and(array_column($resolved['reports']['activation_gaps'], 'code'))
        ->each->toBe('formation_assignment_assumption_not_recorded');
});

test('an evidenced independently verified assumption activates only its exact assignment', function () {
    $resolved = resolveRoleActivations([managingPartnerAssumption()]);
    $admission = $resolved['assignment_activation_admissions'][0];

    expect($resolved['counts']['admitted_activations'])->toBe(1)
        ->and($resolved['counts']['pending_assignments'])->toBe(7)
        ->and($admission['assignment_key'])->toBe('managing-partner-angelica')
        ->and($admission['identity_key'])->toBe('angelica-santos')
        ->and($admission['activates_exact_assignment'])->toBeTrue()
        ->and($admission['grants_firm_authority'])->toBeFalse()
        ->and($admission['authority_effect'])->toBe('eligible_for_separate_authority_resolution');
});

test('an assumption cannot activate before verified Firm Commencement', function () {
    $resolved = resolveRoleActivations([managingPartnerAssumption()], roleActivationUncommencedFormation());

    expect($resolved['counts']['commencement_eligible_assignments'])->toBe(0)
        ->and($resolved['assignment_activation_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['activation_gaps'], 'code'))
        ->toContain('verified_firm_commencement_unavailable', 'invalid_role_activation_commencement_basis');
});

test('a holder cannot independently verify their own assumption', function () {
    $record = managingPartnerAssumption();
    $record['independent_verification']['identity_key'] = 'angelica-santos';
    $resolved = resolveRoleActivations([$record]);

    expect($resolved['assignment_activation_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['verification_gaps'], 'code'))
        ->toContain('invalid_independent_verification');
});

test('an assignment snapshot mismatch cannot be admitted', function () {
    $record = managingPartnerAssumption();
    $record['assignment_snapshot']['role_key'] = 'client-delivery';
    $resolved = resolveRoleActivations([$record]);

    expect($resolved['assignment_activation_admissions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('role_activation_snapshot_mismatch');
});
