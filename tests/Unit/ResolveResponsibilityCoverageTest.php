<?php

use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvePolicyRegistry;
use App\ResponsibilityCoverage\ResolveResponsibilityCoverage;
use App\ResponsibilityCoverage\ResponsibilityCoverageDefinition;

function responsibilityCoverageDefinition(): ResponsibilityCoverageDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/responsibility-coverage.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return ResponsibilityCoverageDefinition::fromArray($definition);
}

function responsibilityPartnershipDefinition(): PartnershipDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PartnershipDefinition::fromArray($definition);
}

function responsibilityPolicyDefinition(): PolicyRegistryDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PolicyRegistryDefinition::fromArray($definition);
}

/** @return array<string, mixed> */
function resolveResponsibilityCoverage(
    ?ResponsibilityCoverageDefinition $coverage = null,
    ?PartnershipDefinition $partnership = null,
    ?PolicyRegistryDefinition $policies = null,
): array {
    return (new ResolveResponsibilityCoverage)->handle(
        $coverage ?? responsibilityCoverageDefinition(),
        (new ResolvePartnership)->handle($partnership ?? responsibilityPartnershipDefinition()),
        (new ResolvePolicyRegistry)->handle($policies ?? responsibilityPolicyDefinition()),
    )->toArray();
}

test('it exposes canonical vacancies succession gaps concentration and pending policy requirements', function () {
    $resolved = resolveResponsibilityCoverage();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['requirements'])->toBe(15)
        ->and($resolved['counts']['covered'])->toBe(9)
        ->and($resolved['counts']['vacant'])->toBe(2)
        ->and($resolved['counts']['pending_activation'])->toBe(4)
        ->and($resolved['counts']['conflicted'])->toBe(0)
        ->and($resolved['counts']['succession_gaps'])->toBe(4)
        ->and($resolved['counts']['concentration_exposures'])->toBe(1)
        ->and(array_column($resolved['reports']['vacancies'], 'requirement_key'))
        ->toBe(['security-compliance', 'privileged-emergency-access'])
        ->and($resolved['reports']['concentration_exposures'][0]['holder_key'])->toBe('angelica-santos')
        ->and($resolved['reports']['concentration_exposures'][0]['requirement_keys'])
        ->toBe(['managing-partner-office', 'day-to-day-management', 'client-delivery', 'cloud-operations']);
});

test('it keeps office authority and personal constitutional rights separate', function () {
    $resolved = resolveResponsibilityCoverage();
    $requirements = collect($resolved['requirements'])->keyBy('key');

    expect($requirements['managing-partner-office']['authority_attachment'])->toBe('office')
        ->and($requirements['managing-partner-office']['holder_source']['type'])->toBe('office')
        ->and($requirements['managing-partner-office']['holder_names'])->toBe(['Angelica Anaïs C. Santos'])
        ->and($requirements['constitutional-governance']['authority_attachment'])->toBe('partner_status')
        ->and($requirements['constitutional-governance']['holder_names'])
        ->toBe(['Lester B. Hurtado', 'Angelica Anaïs C. Santos']);
});

test('a Draft policy creates a pending design requirement rather than a live vacancy', function () {
    $resolved = resolveResponsibilityCoverage();
    $productionAccess = collect($resolved['requirements'])->firstWhere('key', 'production-access-approval');

    expect($productionAccess['source_status'])->toBe('draft')
        ->and($productionAccess['coverage_status'])->toBe('pending_activation')
        ->and(array_column($resolved['reports']['vacancies'], 'requirement_key'))
        ->not->toContain('production-access-approval');
});

test('an Effective policy turns its unassigned authority into a visible vacancy', function () {
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $policyIndex = array_search('production-access', array_column($definition['policies'], 'key'), true);
    $definition['policies'][$policyIndex]['versions'][0]['status'] = 'effective';
    $policies = PolicyRegistryDefinition::fromArray($definition);
    $resolved = resolveResponsibilityCoverage(policies: $policies);
    $productionAccess = collect($resolved['requirements'])->firstWhere('key', 'production-access-approval');

    expect($productionAccess['source_status'])->toBe('operative')
        ->and($productionAccess['coverage_status'])->toBe('vacant')
        ->and(array_column($resolved['reports']['vacancies'], 'requirement_key'))
        ->toContain('production-access-approval');
});

test('it detects an unqualified holder without treating assignment as authority', function () {
    $partnership = responsibilityPartnershipDefinition();
    $formation = $partnership->formation;
    $formation['founding_partners'][1]['partner_status'] = 'Associate';
    $modified = new PartnershipDefinition(
        schemaVersion: $partnership->schemaVersion,
        formation: $formation,
        constitution: $partnership->constitution,
        decisions: $partnership->decisions,
    );
    $resolved = resolveResponsibilityCoverage(partnership: $modified);

    expect(array_column($resolved['reports']['qualification_gaps'], 'requirement_key'))
        ->toContain('managing-partner-office', 'constitutional-governance', 'client-delivery')
        ->and($resolved['compiler_status'])->toBe('consistent_with_gaps');
});

test('it detects prohibited combinations independently from vacancy coverage', function () {
    $partnership = responsibilityPartnershipDefinition();
    $constitution = $partnership->constitution;
    $assignmentIndex = array_search(
        'privileged-emergency-access',
        array_column($constitution['responsibility_assignments'], 'key'),
        true,
    );
    $constitution['responsibility_assignments'][$assignmentIndex]['holders'] = ['angelica-santos'];
    $modified = new PartnershipDefinition(
        schemaVersion: $partnership->schemaVersion,
        formation: $partnership->formation,
        constitution: $constitution,
        decisions: $partnership->decisions,
    );
    $resolved = resolveResponsibilityCoverage(partnership: $modified);

    expect($resolved['reports']['separation_conflicts'])->toHaveCount(1)
        ->and($resolved['reports']['separation_conflicts'][0]['constraint_key'])
        ->toBe('emergency-access-approval-independent-from-cloud-operations')
        ->and($resolved['compiler_status'])->toBe('conflict_detected');
});

test('a current holder cannot satisfy their own succession coverage', function () {
    $definition = responsibilityCoverageDefinition();
    $requirements = $definition->requirements;
    $requirementIndex = array_search('managing-partner-office', array_column($requirements, 'key'), true);
    $requirements[$requirementIndex]['succession']['alternate_holder_keys'] = ['angelica-santos'];
    $modified = new ResponsibilityCoverageDefinition(
        schemaVersion: $definition->schemaVersion,
        concentrationReviewThreshold: $definition->concentrationReviewThreshold,
        requirements: $requirements,
        separationConstraints: $definition->separationConstraints,
    );
    $resolved = resolveResponsibilityCoverage(coverage: $modified);

    expect(array_column($resolved['reports']['succession_gaps'], 'requirement_key'))
        ->toContain('managing-partner-office');
});
