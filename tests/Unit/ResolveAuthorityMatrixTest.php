<?php

use App\AuthorityMatrix\AuthorityMatrixDefinition;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\FormationCompletion\ResolvedFormationCompletion;
use App\IdentityAndRoles\IdentityAndRoleDefinition;
use App\IdentityAndRoles\ResolveIdentityAndRoles;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;
use App\ResponsibilityCoverage\ResolveResponsibilityCoverage;
use App\ResponsibilityCoverage\ResponsibilityCoverageDefinition;
use App\RoleActivations\ResolvedRoleActivations;
use DateTimeImmutable;

function authorityMatrixDefinition(): AuthorityMatrixDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/authority-matrix.json'), true, flags: JSON_THROW_ON_ERROR);

    return AuthorityMatrixDefinition::fromArray($definition);
}

function authorityMatrixPartnershipDefinition(): PartnershipDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/partnership.json'), true, flags: JSON_THROW_ON_ERROR);

    return PartnershipDefinition::fromArray($definition);
}

function authorityMatrixPolicyDefinition(): PolicyRegistryDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/policies.json'), true, flags: JSON_THROW_ON_ERROR);

    return PolicyRegistryDefinition::fromArray($definition);
}

function authorityMatrixCoverageDefinition(): ResponsibilityCoverageDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/responsibility-coverage.json'), true, flags: JSON_THROW_ON_ERROR);

    return ResponsibilityCoverageDefinition::fromArray($definition);
}

function authorityMatrixIdentityDefinition(): IdentityAndRoleDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/identity-and-roles.json'), true, flags: JSON_THROW_ON_ERROR);

    return IdentityAndRoleDefinition::fromArray($definition);
}

/** @param array<string, mixed> $changes */
function authorityMatrixModifiedDefinition(array $changes): AuthorityMatrixDefinition
{
    $definition = authorityMatrixDefinition();

    return new AuthorityMatrixDefinition(
        schemaVersion: $definition->schemaVersion,
        governingPolicy: $changes['governing_policy'] ?? $definition->governingPolicy,
        domains: $changes['domains'] ?? $definition->domains,
        entries: $changes['entries'] ?? $definition->entries,
        deferredDecisions: $changes['deferred_decisions'] ?? $definition->deferredDecisions,
        evidenceRecords: $changes['evidence_records'] ?? $definition->evidenceRecords,
    );
}

function authorityMatrixEffectivePartnership(): PartnershipDefinition
{
    $definition = authorityMatrixPartnershipDefinition();
    $formation = $definition->formation;
    $formation['firm']['effective_date'] = '2026-01-01T00:00:00+08:00';

    return new PartnershipDefinition($definition->schemaVersion, $formation, $definition->constitution, $definition->decisions);
}

/** @param list<string> $policyKeys */
function authorityMatrixEffectivePolicies(ResolvedPolicyRegistry $registry, array $policyKeys): ResolvedPolicyRegistry
{
    $policies = $registry->policies;
    foreach ($policies as &$policy) {
        if (! in_array($policy['key'], $policyKeys, true)) {
            continue;
        }
        $policy['current_status'] = 'effective';
        $policy['current_status_label'] = 'Effective';
        $policy['current']['status'] = 'effective';
        $policy['current']['effective_at'] = '2026-01-01T00:00:00+08:00';
        $policy['current']['operative'] = true;
    }
    unset($policy);

    return new ResolvedPolicyRegistry(
        $registry->schemaVersion,
        $policies,
        $registry->exceptions,
        $registry->evidenceRecords,
        $registry->statusCounts,
        $registry->conflicts,
        $registry->lifecycleGaps,
        $registry->evidenceGaps,
    );
}

/** @return array<string, mixed> */
function authorityMatrixEvidence(string $key, string $recordType): array
{
    return [
        'key' => $key,
        'record_type' => $recordType,
        'actor' => 'Founding Partners',
        'occurred_at' => '2026-01-01T00:00:00+08:00',
        'source' => 'Partnership formation',
        'reason' => 'Activate an institutionally approved authority record.',
        'approval' => 'Founding formation decision',
        'state' => 'accepted',
        'supporting_evidence' => ['partnership-definition'],
    ];
}

/**
 * @param  Closure(ResolvedPolicyRegistry): ResolvedPolicyRegistry|null  $policyTransform
 * @return array<string, mixed>
 */
function resolveAuthorityMatrixCompiler(
    ?AuthorityMatrixDefinition $matrix = null,
    ?PartnershipDefinition $partnership = null,
    ?IdentityAndRoleDefinition $identityAndRoles = null,
    ?Closure $policyTransform = null,
    ?ResolvedRoleActivations $roleActivations = null,
): array {
    $resolvedPartnership = (new ResolvePartnership)->handle($partnership ?? authorityMatrixPartnershipDefinition());
    $resolvedPolicies = (new ResolvePolicyRegistry)->handle(authorityMatrixPolicyDefinition());
    if ($policyTransform !== null) {
        $resolvedPolicies = $policyTransform($resolvedPolicies);
    }
    $resolvedCoverage = (new ResolveResponsibilityCoverage)->handle(
        authorityMatrixCoverageDefinition(),
        $resolvedPartnership,
        $resolvedPolicies,
    );
    $resolvedIdentities = (new ResolveIdentityAndRoles)->handle(
        $identityAndRoles ?? authorityMatrixIdentityDefinition(),
        $resolvedPartnership,
        $resolvedCoverage,
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
        $partnership !== null && $partnership->formation['firm']['effective_date'] !== null
            ? authorityMatrixFormationCompletion($partnership->formation['firm']['effective_date'])
            : null,
        $roleActivations,
    );

    return (new ResolveAuthorityMatrix)->handle(
        $matrix ?? authorityMatrixDefinition(),
        $resolvedPartnership,
        $resolvedPolicies,
        $resolvedCoverage,
        $resolvedIdentities,
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

function authorityMatrixManagingPartnerActivation(): ResolvedRoleActivations
{
    return new ResolvedRoleActivations(
        schemaVersion: 1,
        requirements: [],
        candidates: [],
        activationRecords: [],
        assignmentActivationAdmissions: [[
            'key' => 'assumption::managing-partner-angelica',
            'assignment_key' => 'managing-partner-angelica',
            'role_key' => 'managing-partner',
            'identity_key' => 'angelica-santos',
            'effective_at' => '2026-01-01T00:00:00+08:00',
            'activates_exact_assignment' => true,
            'grants_firm_authority' => false,
        ]],
        evidenceRecords: [],
        conflicts: [],
        activationGaps: [],
        acceptanceGaps: [],
        verificationGaps: [],
        evidenceGaps: [],
    );
}

function authorityMatrixFormationCompletion(string $effectiveAt): ResolvedFormationCompletion
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

test('it exposes the canonical matrix without creating effective authority', function () {
    $resolved = resolveAuthorityMatrixCompiler();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['domains'])->toBe(7)
        ->and($resolved['counts']['actions'])->toBe(7)
        ->and($resolved['counts']['entries'])->toBe(7)
        ->and($resolved['counts']['deferred_decisions'])->toBe(7)
        ->and($resolved['counts']['effective_entries'])->toBe(0)
        ->and($resolved['counts']['effective_holders'])->toBe(0)
        ->and($resolved['counts']['by_lifecycle']['approved'])->toBe(3)
        ->and($resolved['counts']['by_lifecycle']['design'])->toBe(4)
        ->and($resolved['counts']['by_resolution']['pending_activation'])->toBe(2)
        ->and($resolved['counts']['by_resolution']['vacant_holder'])->toBe(1)
        ->and($resolved['counts']['by_resolution']['design_only'])->toBe(4);
});

test('it resolves personal constitutional status and office assignment as different holder sources', function () {
    $resolved = resolveAuthorityMatrixCompiler();
    $entries = collect($resolved['entries'])->keyBy('key');

    expect($entries['founding-partner-reserved-matter-participation']['holder_rule']['type'])->toBe('partner_status')
        ->and($entries['founding-partner-reserved-matter-participation']['candidate_holder_names'])
        ->toBe(['Lester B. Hurtado', 'Angelica Anaïs C. Santos'])
        ->and($entries['managing-partner-ordinary-management']['holder_rule']['type'])->toBe('role')
        ->and($entries['managing-partner-ordinary-management']['candidate_holder_names'])
        ->toBe(['Angelica Anaïs C. Santos']);
});

test('it keeps Firm Authority Client Mandate and Specific Approval separate', function () {
    $resolved = resolveAuthorityMatrixCompiler();
    $entry = collect($resolved['entries'])->firstWhere('key', 'production-access-approval');

    expect($entry['scope']['authority_boundary'])->toBe('firm_authority_only')
        ->and($entry['client_mandate_gate'])->toBe('required_separately')
        ->and($entry['specific_approval_gate'])->toBe('required_separately')
        ->and($entry['authorizes_client_action'])->toBeFalse();
});

test('a professional responsibility role cannot be substituted for an authority-bearing office', function () {
    $definition = authorityMatrixDefinition();
    $entries = $definition->entries;
    $index = array_search('managing-partner-ordinary-management', array_column($entries, 'key'), true);
    $entries[$index]['holder_rule']['key'] = 'client-delivery';
    $resolved = resolveAuthorityMatrixCompiler(authorityMatrixModifiedDefinition(['entries' => $entries]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('invalid_authority_holder_rule')
        ->and($resolved['counts']['effective_entries'])->toBe(0);
});

test('an active entry without evidence cannot grant Firm Authority', function () {
    $definition = authorityMatrixDefinition();
    $entries = $definition->entries;
    $index = array_search('managing-partner-ordinary-management', array_column($entries, 'key'), true);
    $entries[$index]['lifecycle_status'] = 'active';
    $resolved = resolveAuthorityMatrixCompiler(authorityMatrixModifiedDefinition(['entries' => $entries]));

    expect(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('missing_authority_evidence')
        ->and($resolved['counts']['effective_entries'])->toBe(0);
});

test('an unresolved threshold blocks an otherwise complete authority entry', function () {
    $definition = authorityMatrixDefinition();
    $entries = $definition->entries;
    $index = array_search('managing-partner-ordinary-management', array_column($entries, 'key'), true);
    $entries[$index]['scope']['thresholds']['monetary_status'] = 'unresolved';
    $resolved = resolveAuthorityMatrixCompiler(authorityMatrixModifiedDefinition(['entries' => $entries]));

    expect(array_column($resolved['reports']['boundary_gaps'], 'code'))
        ->toContain('authority_threshold_unresolved')
        ->and($resolved['counts']['effective_entries'])->toBe(0);
});

test('a controlled approval action cannot permit self approval', function () {
    $definition = authorityMatrixDefinition();
    $entries = $definition->entries;
    $index = array_search('production-access-approval', array_column($entries, 'key'), true);
    $entries[$index]['separation']['self_approval_permitted'] = true;
    $resolved = resolveAuthorityMatrixCompiler(authorityMatrixModifiedDefinition(['entries' => $entries]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('self_approval_authority')
        ->and($resolved['compiler_status'])->toBe('conflict_detected');
});

test('a fully active office entry grants bounded Firm Authority to its operative holder', function () {
    $activeIdentities = authorityMatrixIdentityDefinition();
    $matrixDefinition = authorityMatrixDefinition();
    $entries = $matrixDefinition->entries;
    $entryIndex = array_search('managing-partner-ordinary-management', array_column($entries, 'key'), true);
    $entries[$entryIndex]['lifecycle_status'] = 'active';
    $entries[$entryIndex]['evidence_record_key'] = 'evidence-management-authority';
    $activeMatrix = authorityMatrixModifiedDefinition([
        'entries' => $entries,
        'evidence_records' => [authorityMatrixEvidence('evidence-management-authority', 'Authority Grant Record')],
    ]);
    $resolved = resolveAuthorityMatrixCompiler(
        $activeMatrix,
        authorityMatrixEffectivePartnership(),
        $activeIdentities,
        fn (ResolvedPolicyRegistry $policies): ResolvedPolicyRegistry => authorityMatrixEffectivePolicies($policies, ['authority-and-delegation']),
        authorityMatrixManagingPartnerActivation(),
    );
    $entry = collect($resolved['entries'])->firstWhere('key', 'managing-partner-ordinary-management');

    expect($entry['grants_firm_authority'])->toBeTrue()
        ->and($entry['effective_holder_names'])->toBe(['Angelica Anaïs C. Santos'])
        ->and($entry['authorizes_client_action'])->toBeFalse()
        ->and($resolved['counts']['effective_entries'])->toBe(1);
});

test('an authority source cannot be silently substituted', function () {
    $definition = authorityMatrixDefinition();
    $entries = $definition->entries;
    $entries[0]['authority_source']['reference'] = 'constitution.offices.managing-partner';
    $resolved = resolveAuthorityMatrixCompiler(authorityMatrixModifiedDefinition(['entries' => $entries]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('authority_source_mismatch')
        ->and($resolved['compiler_status'])->toBe('conflict_detected');
});
