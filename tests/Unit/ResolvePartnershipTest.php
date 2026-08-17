<?php

use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;

function partnershipDefinition(): PartnershipDefinition
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PartnershipDefinition::fromArray($definition);
}

test('it resolves founding governance economics and management independently', function () {
    $resolved = (new ResolvePartnership)->handle(partnershipDefinition())->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_open_decisions')
        ->and($resolved['formation']['founding_partners'])->toHaveCount(2)
        ->and($resolved['projections']['partnership'][0]['governance_weight'])->toBe(50)
        ->and($resolved['projections']['partnership'][0]['economic_allocation'])->toBe(30)
        ->and($resolved['projections']['management'][0]['holder_name'])->toBe('Angelica Anaïs C. Santos')
        ->and($resolved['projections']['economics']['firm_allocation']['recipient_type'])->toBe('firm')
        ->and($resolved['projections']['economics']['firm_allocation']['percentage'])->toBe(20);
});

test('it exposes unresolved decisions counsel review and responsibility gaps', function () {
    $resolved = (new ResolvePartnership)->handle(partnershipDefinition())->toArray();

    expect($resolved['reports']['decision_gaps'])
        ->toHaveCount(6)
        ->and(array_column($resolved['reports']['decision_gaps'], 'key'))
        ->toContain('initial-capital-contributions', 'capital-ownership', 'governance-deadlock', 'partner-retirement', 'death-incapacity', 'buyout-redemption')
        ->and(array_column($resolved['reports']['counsel_review'], 'key'))
        ->toContain('founding-governance', 'founder-dilution', 'death-incapacity')
        ->and(array_column($resolved['reports']['responsibility_gaps'], 'key'))
        ->toContain('security-compliance', 'privileged-emergency-access');
});

test('it detects an invalid governance total without filling the gap', function () {
    $definition = partnershipDefinition();
    $formation = $definition->formation;
    $formation['founding_partners'][0]['governance_weight'] = 40;

    $invalidDefinition = new PartnershipDefinition(
        schemaVersion: $definition->schemaVersion,
        formation: $formation,
        constitution: $definition->constitution,
        decisions: $definition->decisions,
    );

    $resolved = (new ResolvePartnership)->handle($invalidDefinition)->toArray();
    $governanceCheck = collect($resolved['reports']['consistency'])
        ->firstWhere('code', 'governance_total');

    expect($governanceCheck['status'])->toBe('failed')
        ->and($governanceCheck['message'])->toBe('Founding governance totals 90%.')
        ->and($resolved['compiler_status'])->toBe('conflict_detected');
});

test('the canonical definition never collapses institutional dimensions into partner percentage', function () {
    $definition = file_get_contents(__DIR__.'/../../resources/institution/partnership.json');

    expect($definition)->not->toContain('partner_percentage');
});
