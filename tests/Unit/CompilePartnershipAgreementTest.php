<?php

use App\Partnership\CompilePartnershipAgreement;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;

function compilationPartnershipDefinition(): PartnershipDefinition
{
    /** @var array<string, mixed> $definition */
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return PartnershipDefinition::fromArray($definition);
}

test('it deterministically compiles a working draft with visible gaps and counsel review', function () {
    $definition = compilationPartnershipDefinition();
    $resolved = app(ResolvePartnership::class)->handle($definition);
    $compiledAt = new DateTimeImmutable('2026-08-19T10:00:00+08:00');

    $compilation = app(CompilePartnershipAgreement::class)->handle($definition, $resolved, $compiledAt);
    $result = $compilation->toArray();

    expect($result['status'])->toBe('working_draft')
        ->and($result['counts']['resolved_provisions'])->toBeGreaterThan(0)
        ->and($result['counts']['decisions_required'])->toBeGreaterThan(0)
        ->and($result['counts']['counsel_review'])->toBeGreaterThan(0)
        ->and($result['counts']['conflicts'])->toBe(0)
        ->and($result['agreement']['markdown'])->toContain('[UNRESOLVED]')
        ->and($result['agreement']['markdown'])->toContain('[COUNSEL REVIEW]')
        ->and($result['agreement']['markdown'])->toContain('AI can perform the work. The Firm accepts responsibility for the work.')
        ->and($result['agreement']['markdown'])->toContain('professional accountability')
        ->and($result['agreement']['markdown'])->toContain('Firm Allocation')
        ->and($result['agreement_fingerprint'])->toHaveLength(64);
});

test('the same accepted source state produces the same agreement fingerprint', function () {
    $definition = compilationPartnershipDefinition();
    $resolved = app(ResolvePartnership::class)->handle($definition);
    $compiler = app(CompilePartnershipAgreement::class);
    $compiledAt = new DateTimeImmutable('2026-08-19T10:00:00+08:00');

    $first = $compiler->handle($definition, $resolved, $compiledAt)->toArray();
    $second = $compiler->handle($definition, $resolved, $compiledAt)->toArray();

    expect($first['source_fingerprint'])->toBe($second['source_fingerprint'])
        ->and($first['agreement_fingerprint'])->toBe($second['agreement_fingerprint'])
        ->and($first['compilation_id'])->toBe($second['compilation_id'])
        ->and($first['agreement']['markdown'])->toBe($second['agreement']['markdown']);
});

test('compilation does not mutate the Partnership Definition', function () {
    $definition = compilationPartnershipDefinition();
    $snapshot = json_encode($definition->formation, JSON_THROW_ON_ERROR);
    $resolved = app(ResolvePartnership::class)->handle($definition);

    app(CompilePartnershipAgreement::class)->handle($definition, $resolved, new DateTimeImmutable('2026-08-19T10:00:00+08:00'));

    expect(json_encode($definition->formation, JSON_THROW_ON_ERROR))->toBe($snapshot);
});

test('indefinite duration preserves commencement and related decision boundaries', function () {
    $definition = compilationPartnershipDefinition();
    $resolved = app(ResolvePartnership::class)->handle($definition);
    $result = app(CompilePartnershipAgreement::class)->handle($definition, $resolved)->toArray();
    $gaps = array_column($result['decision_gaps'], 'key');
    $reviews = array_column($result['counsel_review'], 'key');

    expect(array_column($result['resolved_provisions'], 'key'))->toContain('partnership-term')
        ->and($gaps)->not->toContain('partnership-term')
        ->and($gaps)->toContain('formation-commencement', 'partnership-dissolution', 'initial-capital-contributions', 'capital-ownership', 'governance-deadlock', 'partner-retirement', 'death-incapacity', 'buyout-redemption')
        ->and($reviews)->toContain('partnership-term', 'partnership-dissolution')
        ->and($definition->formation['firm']['effective_date'])->toBeNull()
        ->and($result['agreement']['markdown'])->toContain('Indefinite institutional duration', 'Partners may change. The Firm is intended to endure.', 'one of only two Partners', 'Professional and governance authority does not automatically pass to heirs.', 'Commencement remains unresolved')
        ->and($result['counts'])->toBe([
            'resolved_provisions' => 14,
            'decisions_required' => 10,
            'counsel_review' => 11,
            'conflicts' => 0,
        ]);
});

test('missing term remains visible despite a continuity preference', function () {
    $definition = compilationPartnershipDefinition();
    $formation = $definition->formation;
    unset($formation['term']);
    $definition = new PartnershipDefinition(
        schemaVersion: $definition->schemaVersion,
        formation: $formation,
        constitution: $definition->constitution,
        decisions: $definition->decisions,
    );
    $resolved = app(ResolvePartnership::class)->handle($definition);
    $result = app(CompilePartnershipAgreement::class)->handle($definition, $resolved)->toArray();

    expect(array_column($result['decision_gaps'], 'key'))->toContain('partnership-term')
        ->and(array_column($result['resolved_provisions'], 'key'))->not->toContain('partnership-term')
        ->and($result['agreement']['markdown'])->toContain('[UNRESOLVED] The Partnership term has not been determined.');
});
