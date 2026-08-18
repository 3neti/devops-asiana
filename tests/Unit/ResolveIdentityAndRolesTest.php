<?php

use App\FormationCompletion\ResolvedFormationCompletion;
use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\IdentityAndRoles\ResolveIdentityAndRoles;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvePolicyRegistry;
use App\ResponsibilityCoverage\ResolveResponsibilityCoverage;
use App\ResponsibilityCoverage\ResponsibilityCoverageDefinition;
use DateTimeImmutable;

function identityRoleDefinition(): IdentityAndRoleDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/identity-and-roles.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return IdentityAndRoleDefinition::fromArray($definition);
}

function identityRolePartnershipDefinition(): PartnershipDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PartnershipDefinition::fromArray($definition);
}

function identityRolePolicyDefinition(): PolicyRegistryDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PolicyRegistryDefinition::fromArray($definition);
}

function identityRoleCoverageDefinition(): ResponsibilityCoverageDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/responsibility-coverage.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return ResponsibilityCoverageDefinition::fromArray($definition);
}

/** @return array<string, mixed> */
function resolveIdentityRoles(
    ?IdentityAndRoleDefinition $identityAndRoles = null,
    ?PartnershipDefinition $partnership = null,
    bool $formationCommenced = true,
): array {
    $partnershipDefinition = $partnership ?? identityRolePartnershipDefinition();
    $resolvedPartnership = (new ResolvePartnership)->handle($partnershipDefinition);
    $resolvedCoverage = (new ResolveResponsibilityCoverage)->handle(
        identityRoleCoverageDefinition(),
        $resolvedPartnership,
        (new ResolvePolicyRegistry)->handle(identityRolePolicyDefinition()),
    );

    return (new ResolveIdentityAndRoles)->handle(
        $identityAndRoles ?? identityRoleDefinition(),
        $resolvedPartnership,
        $resolvedCoverage,
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
        $formationCommenced && $partnershipDefinition->formation['firm']['effective_date'] !== null
            ? identityRoleFormationCompletion($partnershipDefinition->formation['firm']['effective_date'])
            : null,
    )->toArray();
}

function identityRoleFormationCompletion(string $effectiveAt): ResolvedFormationCompletion
{
    return new ResolvedFormationCompletion(
        schemaVersion: 1,
        requirements: [],
        legalRequirementsRule: [],
        capitalInitialization: [],
        commencementRecords: [],
        officeActivationBases: [[
            'effective_at' => $effectiveAt,
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

/** @param array<string, mixed> $changes */
function identityRoleModifiedDefinition(array $changes): IdentityAndRoleDefinition
{
    $definition = identityRoleDefinition();

    return new IdentityAndRoleDefinition(
        schemaVersion: $definition->schemaVersion,
        identities: $changes['identities'] ?? $definition->identities,
        roles: $changes['roles'] ?? $definition->roles,
        assignments: $changes['assignments'] ?? $definition->assignments,
        evidenceRecords: $changes['evidence_records'] ?? $definition->evidenceRecords,
    );
}

function identityRoleEffectivePartnership(): PartnershipDefinition
{
    $definition = identityRolePartnershipDefinition();
    $formation = $definition->formation;
    $formation['firm']['effective_date'] = '2026-01-01T00:00:00+08:00';

    return new PartnershipDefinition(
        schemaVersion: $definition->schemaVersion,
        formation: $formation,
        constitution: $definition->constitution,
        decisions: $definition->decisions,
    );
}

/** @return array<string, mixed> */
function identityRoleEvidence(string $key): array
{
    return [
        'key' => $key,
        'record_type' => 'Role Assignment Record',
        'actor' => 'Founding Partners',
        'occurred_at' => '2026-01-01T00:00:00+08:00',
        'source' => 'Partnership formation',
        'reason' => 'Record the operative formation assignment.',
        'approval' => 'Formation decision',
        'state' => 'accepted',
        'supporting_evidence' => ['partnership-definition'],
    ];
}

test('it exposes canonical identities roles assignments and activation gaps', function () {
    $resolved = resolveIdentityRoles();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['identities'])->toBe(2)
        ->and($resolved['counts']['roles'])->toBe(9)
        ->and($resolved['counts']['assignments'])->toBe(8)
        ->and($resolved['counts']['authority_effective'])->toBe(0)
        ->and($resolved['counts']['authentication_bindings'])->toBe(0)
        ->and($resolved['counts']['by_assignment_lifecycle']['approved'])->toBe(8)
        ->and($resolved['counts']['by_role_coverage']['pending_activation'])->toBe(7)
        ->and($resolved['counts']['by_role_coverage']['vacant'])->toBe(2)
        ->and($resolved['reports']['activation_gaps'])->toHaveCount(1)
        ->and($resolved['reports']['holder_mismatches'])->toBeEmpty();
});

test('identity derives professional status without becoming a login or employment classification', function () {
    $resolved = resolveIdentityRoles();
    $identities = collect($resolved['identities'])->keyBy('key');

    expect($identities['lester-hurtado']['display_name'])->toBe('Lester B. Hurtado')
        ->and($identities['lester-hurtado']['partner_status'])->toBe('Founding Partner')
        ->and($identities['lester-hurtado']['authentication_bound'])->toBeFalse()
        ->and($identities['lester-hurtado']['employment_relationship']['state'])->toBe('unresolved')
        ->and($resolved['reports']['identity_gaps'])->toHaveCount(2);
});

test('approval does not activate formation assignments while effective time is unresolved', function () {
    $resolved = resolveIdentityRoles();

    expect(array_unique(array_column($resolved['assignments'], 'operational_status')))
        ->toBe(['approved_pending_effective_time'])
        ->and(array_filter($resolved['assignments'], fn (array $assignment): bool => $assignment['operative']))
        ->toBeEmpty();
});

test('role holders reconcile with responsibility coverage without duplicating canonical names', function () {
    $resolved = resolveIdentityRoles();
    $roles = collect($resolved['roles'])->keyBy('key');

    expect($roles['partnership-strategy']['recorded_holder_keys'])
        ->toBe(['lester-hurtado', 'angelica-santos'])
        ->and($roles['managing-partner']['recorded_holder_names'])
        ->toBe(['Angelica Anaïs C. Santos'])
        ->and($roles['security-compliance']['coverage_status'])->toBe('vacant')
        ->and($roles['privileged-emergency-access-approver']['coverage_status'])->toBe('vacant');
});

test('an operative professional responsibility does not itself grant Firm authority', function () {
    $definition = identityRoleDefinition();
    $assignments = $definition->assignments;
    $assignmentIndex = array_search('client-delivery-angelica', array_column($assignments, 'key'), true);
    $assignments[$assignmentIndex]['lifecycle_status'] = 'active';
    $assignments[$assignmentIndex]['evidence_record_key'] = 'evidence-client-delivery';
    $modified = identityRoleModifiedDefinition([
        'assignments' => $assignments,
        'evidence_records' => [identityRoleEvidence('evidence-client-delivery')],
    ]);
    $resolved = resolveIdentityRoles($modified, identityRoleEffectivePartnership());
    $assignment = collect($resolved['assignments'])->firstWhere('key', 'client-delivery-angelica');

    expect($assignment['operative'])->toBeTrue()
        ->and($assignment['grants_firm_authority'])->toBeFalse()
        ->and($resolved['counts']['authority_effective'])->toBe(0);
});

test('a Firm effective date does not activate formation assignments without verified commencement', function () {
    $definition = identityRoleDefinition();
    $assignments = $definition->assignments;
    $assignmentIndex = array_search('client-delivery-angelica', array_column($assignments, 'key'), true);
    $assignments[$assignmentIndex]['lifecycle_status'] = 'active';
    $assignments[$assignmentIndex]['evidence_record_key'] = 'evidence-client-delivery';
    $modified = identityRoleModifiedDefinition([
        'assignments' => $assignments,
        'evidence_records' => [identityRoleEvidence('evidence-client-delivery')],
    ]);

    $resolved = resolveIdentityRoles($modified, identityRoleEffectivePartnership(), false);
    $assignment = collect($resolved['assignments'])->firstWhere('key', 'client-delivery-angelica');

    expect($assignment['operative'])->toBeFalse()
        ->and(array_column($resolved['reports']['activation_gaps'], 'code'))
        ->toContain('formation_commencement_unverified');
});

test('an authentication binding does not create authority', function () {
    $definition = identityRoleDefinition();
    $identities = $definition->identities;
    $identities[0]['authentication_binding'] = ['user_id' => 1];
    $resolved = resolveIdentityRoles(identityRoleModifiedDefinition(['identities' => $identities]));

    expect($resolved['counts']['authentication_bindings'])->toBe(1)
        ->and($resolved['counts']['authority_effective'])->toBe(0);
});

test('active delegated authority remains blocked without bounded scope expiry and evidence', function () {
    $definition = identityRoleDefinition();
    $assignments = $definition->assignments;
    $assignments[] = [
        'key' => 'emergency-access-lester',
        'role_key' => 'privileged-emergency-access-approver',
        'identity_key' => 'lester-hurtado',
        'lifecycle_status' => 'active',
        'basis' => ['type' => 'delegation', 'reference' => 'future.authority-matrix'],
        'effective_at' => '2026-01-01T00:00:00+08:00',
        'effective_at_source' => null,
        'expires_at' => null,
        'authority_scope' => null,
        'approval' => null,
        'evidence_record_key' => null,
        'disposition' => null,
    ];
    $resolved = resolveIdentityRoles(
        identityRoleModifiedDefinition(['assignments' => $assignments]),
        identityRoleEffectivePartnership(),
    );
    $assignment = collect($resolved['assignments'])->firstWhere('key', 'emergency-access-lester');

    expect($assignment['operative'])->toBeFalse()
        ->and($assignment['grants_firm_authority'])->toBeFalse()
        ->and(array_column($resolved['reports']['activation_gaps'], 'code'))
        ->toContain('missing_assignment_approval', 'incomplete_delegated_authority')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('missing_identity_role_evidence');
});

test('exclusive office overlaps are explicit conflicts', function () {
    $definition = identityRoleDefinition();
    $assignments = $definition->assignments;
    $secondOffice = $assignments[0];
    $secondOffice['key'] = 'managing-partner-lester';
    $secondOffice['identity_key'] = 'lester-hurtado';
    $assignments[] = $secondOffice;
    $resolved = resolveIdentityRoles(identityRoleModifiedDefinition(['assignments' => $assignments]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('exclusive_role_overlap')
        ->and($resolved['compiler_status'])->toBe('conflict_detected');
});
