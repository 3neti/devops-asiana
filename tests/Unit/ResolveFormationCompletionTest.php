<?php

use App\FormationCompletion\FormationCompletionDefinition;
use App\FormationCompletion\ResolvedFormationCompletion;
use App\FormationCompletion\ResolveFormationCompletion;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;

/** @return array<string, mixed> */
function formationCompletionArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/formation-completion.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<string, mixed> */
function formationCompletionPartnershipArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<string, mixed> */
function formationCompletionEvidence(string $key, string $subject): array
{
    return [
        'key' => $key,
        'record_type' => 'Formation Completion Evidence',
        'subject' => $subject,
        'actor' => 'Angelica Anaïs C. Santos',
        'recorded_at' => '2026-08-01T12:00:00+08:00',
        'source' => 'Hypothetical counsel-confirmed formation record',
        'reason' => 'Exercise the Formation Completion compiler without asserting canonical legal facts',
        'state' => 'final',
    ];
}

/** @return array{completion: array<string, mixed>, partnership: array<string, mixed>} */
function validFormationCompletionFixture(): array
{
    $completion = formationCompletionArray();
    $partnership = formationCompletionPartnershipArray();
    $partnership['formation']['firm']['principal_office'] = 'Hypothetical Principal Office, Philippines';
    $partnership['formation']['firm']['effective_date'] = '2026-08-02T00:00:00+08:00';
    $completion['legal_requirements_rule'] = [
        'state' => 'resolved',
        'jurisdiction' => 'Philippines',
        'legal_form' => 'General Partnership',
        'required_record_types' => [
            'hypothetical_formation_record',
            'hypothetical_principal_office_record',
        ],
        'legal_state' => 'counsel_confirmed',
        'counsel_confirmation_reference' => 'COUNSEL-REQUIREMENTS-HYPOTHETICAL-001',
    ];
    $completion['capital_initialization'] = [
        'state' => 'resolved',
        'legal_state' => 'counsel_confirmed',
        'counsel_confirmation_reference' => 'COUNSEL-CAPITAL-HYPOTHETICAL-001',
        'contribution_records' => [
            [
                'key' => 'CAPITAL-LESTER-HYPOTHETICAL-001',
                'partner_identity_key' => 'lester-hurtado',
                'contribution_reference' => 'CONTROLLED-CAPITAL-RECORD-LESTER',
                'evidence_record_key' => 'EVD-CAPITAL-LESTER',
            ],
            [
                'key' => 'CAPITAL-ANAIS-HYPOTHETICAL-001',
                'partner_identity_key' => 'angelica-santos',
                'contribution_reference' => 'CONTROLLED-CAPITAL-RECORD-ANAIS',
                'evidence_record_key' => 'EVD-CAPITAL-ANAIS',
            ],
        ],
    ];
    $completion['evidence_records'] = [
        formationCompletionEvidence('EVD-COMMENCEMENT-INSTRUMENT', 'Executed Partnership Agreement'),
        formationCompletionEvidence('EVD-LEGAL-FORMATION', 'Hypothetical formation requirement'),
        formationCompletionEvidence('EVD-LEGAL-OFFICE', 'Hypothetical principal-office requirement'),
        formationCompletionEvidence('EVD-CAPITAL-LESTER', 'Lester initial capital record'),
        formationCompletionEvidence('EVD-CAPITAL-ANAIS', 'Anaïs initial capital record'),
        formationCompletionEvidence('EVD-COMMENCEMENT', 'Firm Commencement Record'),
    ];
    $completion['commencement_records'] = [[
        'key' => 'FIRM-COMMENCEMENT-HYPOTHETICAL-001',
        'status' => 'confirmed',
        'firm_snapshot' => [
            'name' => 'DevOps Asiana',
            'jurisdiction' => 'Philippines',
            'legal_form' => 'General Partnership',
            'principal_office' => 'Hypothetical Principal Office, Philippines',
            'effective_date' => '2026-08-02T00:00:00+08:00',
        ],
        'founding_partner_identity_keys' => ['lester-hurtado', 'angelica-santos'],
        'constitutional_instrument' => [
            'type' => 'partnership_agreement',
            'repository_reference' => 'docs/legal/hypothetical-executed-partnership-agreement.md',
            'content_digest' => str_repeat('a', 64),
            'executed_at' => '2026-08-01T10:00:00+08:00',
            'counsel_confirmed' => true,
            'counsel_confirmation_reference' => 'COUNSEL-INSTRUMENT-HYPOTHETICAL-001',
            'evidence_record_key' => 'EVD-COMMENCEMENT-INSTRUMENT',
        ],
        'legal_requirements_snapshot' => [
            'counsel_confirmation_reference' => 'COUNSEL-REQUIREMENTS-HYPOTHETICAL-001',
            'required_record_types' => [
                'hypothetical_formation_record',
                'hypothetical_principal_office_record',
            ],
        ],
        'legal_requirement_records' => [
            [
                'type' => 'hypothetical_formation_record',
                'reference' => 'HYPOTHETICAL-RECORD-001',
                'completed_at' => '2026-08-01T11:00:00+08:00',
                'evidence_record_key' => 'EVD-LEGAL-FORMATION',
            ],
            [
                'type' => 'hypothetical_principal_office_record',
                'reference' => 'HYPOTHETICAL-OFFICE-001',
                'completed_at' => '2026-08-01T11:30:00+08:00',
                'evidence_record_key' => 'EVD-LEGAL-OFFICE',
            ],
        ],
        'capital_initialization_reference_keys' => [
            'CAPITAL-LESTER-HYPOTHETICAL-001',
            'CAPITAL-ANAIS-HYPOTHETICAL-001',
        ],
        'confirmed_at' => '2026-08-01T12:00:00+08:00',
        'recorded_by_identity_key' => 'angelica-santos',
        'evidence_record_key' => 'EVD-COMMENCEMENT',
    ]];

    return compact('completion', 'partnership');
}

/** @param array{completion: array<string, mixed>, partnership: array<string, mixed>} $fixture */
function resolveFormationCompletionFixture(array $fixture, string $asOf = '2026-08-20T12:00:00+08:00'): ResolvedFormationCompletion
{
    return (new ResolveFormationCompletion)->handle(
        FormationCompletionDefinition::fromArray($fixture['completion']),
        (new ResolvePartnership)->handle(PartnershipDefinition::fromArray($fixture['partnership'])),
        new DateTimeImmutable($asOf),
    );
}

test('it exposes canonical commencement uncertainty without inventing legal requirements', function () {
    $resolved = resolveFormationCompletionFixture([
        'completion' => formationCompletionArray(),
        'partnership' => formationCompletionPartnershipArray(),
    ])->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->firm_commenced->toBeFalse()
        ->and($resolved['counts']['verified_commencements'])->toBe(0)
        ->and($resolved['counts']['office_activation_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['formation_gaps'], 'code'))
        ->toContain('principal_office_unresolved', 'formation_effective_date_unresolved', 'commencement_not_recorded')
        ->and(array_column($resolved['reports']['legal_gaps'], 'code'))
        ->toContain('formation_legal_requirements_unresolved')
        ->and(array_column($resolved['reports']['capital_gaps'], 'code'))
        ->toContain('initial_capital_contributions_unresolved', 'incomplete_founder_capital_initialization');
});

test('it verifies an exact counsel-confirmed and evidenced Firm commencement', function () {
    $resolved = resolveFormationCompletionFixture(validFormationCompletionFixture())->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->firm_commenced->toBeTrue()
        ->and($resolved['counts']['verified_commencements'])->toBe(1)
        ->and($resolved['counts']['office_activation_bases'])->toBe(1)
        ->and($resolved['office_activation_bases'][0]['permits_formation_derived_assignments'])->toBeTrue()
        ->and($resolved['office_activation_bases'][0]['effective_at'])->toBe('2026-08-02T00:00:00+08:00');
});

test('a populated formation date alone never proves commencement', function () {
    $fixture = [
        'completion' => formationCompletionArray(),
        'partnership' => formationCompletionPartnershipArray(),
    ];
    $fixture['partnership']['formation']['firm']['principal_office'] = 'A stated principal office';
    $fixture['partnership']['formation']['firm']['effective_date'] = '2026-08-02T00:00:00+08:00';

    $resolved = resolveFormationCompletionFixture($fixture)->toArray();

    expect($resolved['firm_commenced'])->toBeFalse()
        ->and($resolved['counts']['office_activation_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['formation_gaps'], 'code'))
        ->toContain('commencement_not_recorded');
});

test('it refuses a requirement set that has not been confirmed by Philippine counsel', function () {
    $fixture = validFormationCompletionFixture();
    $fixture['completion']['legal_requirements_rule']['legal_state'] = 'counsel_review';
    $fixture['completion']['legal_requirements_rule']['counsel_confirmation_reference'] = null;
    $fixture['completion']['commencement_records'][0]['legal_requirements_snapshot']['counsel_confirmation_reference'] = null;

    $resolved = resolveFormationCompletionFixture($fixture)->toArray();

    expect($resolved['firm_commenced'])->toBeFalse()
        ->and($resolved['counts']['office_activation_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['counsel_review'], 'code'))
        ->toContain('formation_legal_requirements_counsel_review');
});

test('it never infers capital initialization from governance or Engagement economics', function () {
    $fixture = validFormationCompletionFixture();
    $fixture['completion']['capital_initialization'] = formationCompletionArray()['capital_initialization'];
    $fixture['completion']['commencement_records'][0]['capital_initialization_reference_keys'] = [];

    $resolved = resolveFormationCompletionFixture($fixture)->toArray();

    expect($resolved['firm_commenced'])->toBeFalse()
        ->and(array_column($resolved['reports']['capital_gaps'], 'code'))
        ->toContain('initial_capital_contributions_unresolved', 'incomplete_founder_capital_initialization');
});

test('it rejects a Commencement Record that contradicts the principal office', function () {
    $fixture = validFormationCompletionFixture();
    $fixture['completion']['commencement_records'][0]['firm_snapshot']['principal_office'] = 'Contradictory office';

    $resolved = resolveFormationCompletionFixture($fixture)->toArray();

    expect($resolved['firm_commenced'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('commencement_firm_snapshot_mismatch');
});

test('verified future commencement remains scheduled rather than operative', function () {
    $fixture = validFormationCompletionFixture();

    $resolved = resolveFormationCompletionFixture($fixture, '2026-08-01T13:00:00+08:00')->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->firm_commenced->toBeFalse()
        ->and($resolved['counts']['verified_commencements'])->toBe(1)
        ->and($resolved['counts']['office_activation_bases'])->toBe(0);
});
