<?php

use App\FormationBootstrap\FormationBootstrapDefinition;
use App\FormationBootstrap\ResolvedFormationBootstrap;
use App\FormationBootstrap\ResolveFormationBootstrap;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvePolicyRegistry;

/** @return array<string, mixed> */
function formationBootstrapArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/formation-bootstrap.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<string, mixed> */
function formationBootstrapPartnershipArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<string, mixed> */
function formationBootstrapPolicyArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<string, mixed> */
function formationBootstrapEvidence(string $key, string $subject): array
{
    return [
        'key' => $key,
        'record_type' => 'Formation Ratification Evidence',
        'subject' => $subject,
        'actor' => 'Angelica Anaïs C. Santos',
        'recorded_at' => '2026-08-01T10:05:00+08:00',
        'source' => 'Hypothetical executed formation record',
        'reason' => 'Prove an explicit formation act in the compiler test fixture',
        'state' => 'final',
    ];
}

/** @return array{bootstrap: array<string, mixed>, partnership: array<string, mixed>, policies: array<string, mixed>} */
function validFormationBootstrapFixture(): array
{
    $bootstrap = formationBootstrapArray();
    $partnership = formationBootstrapPartnershipArray();
    $policies = formationBootstrapPolicyArray();
    $partnership['formation']['firm']['effective_date'] = '2026-08-02T00:00:00+08:00';
    $bootstrap['consent_rule'] = [
        'state' => 'resolved',
        'method' => 'unanimous_founding_partners',
        'legal_state' => 'counsel_confirmed',
        'counsel_confirmation_reference' => 'COUNSEL-HYPOTHETICAL-001',
    ];
    $bootstrap['evidence_records'] = [
        formationBootstrapEvidence('EVD-FORMATION-INSTRUMENT', 'Executed Partnership Agreement'),
        formationBootstrapEvidence('EVD-FORMATION-LESTER', 'Lester B. Hurtado consent'),
        formationBootstrapEvidence('EVD-FORMATION-ANAIS', 'Angelica Anaïs C. Santos consent'),
        formationBootstrapEvidence('EVD-FORMATION-RATIFICATION', 'Formation ratification'),
    ];
    $bootstrap['ratification_records'] = [[
        'key' => 'FORMATION-RATIFICATION-0001',
        'status' => 'ratified',
        'formation_instrument' => [
            'type' => 'partnership_agreement',
            'repository_reference' => 'docs/legal/hypothetical-executed-partnership-agreement.md',
            'content_digest' => str_repeat('a', 64),
            'executed_at' => '2026-08-01T10:00:00+08:00',
            'effective_at' => '2026-08-02T00:00:00+08:00',
            'counsel_confirmed' => true,
            'counsel_confirmation_reference' => 'COUNSEL-HYPOTHETICAL-001',
            'evidence_record_key' => 'EVD-FORMATION-INSTRUMENT',
        ],
        'founding_partner_consents' => [
            [
                'identity_key' => 'lester-hurtado',
                'decision' => 'consent',
                'signed_at' => '2026-08-01T09:45:00+08:00',
                'evidence_record_key' => 'EVD-FORMATION-LESTER',
            ],
            [
                'identity_key' => 'angelica-santos',
                'decision' => 'consent',
                'signed_at' => '2026-08-01T09:50:00+08:00',
                'evidence_record_key' => 'EVD-FORMATION-ANAIS',
            ],
        ],
        'initial_policy_approvals' => array_map(
            static fn (array $policy): array => [
                'policy_key' => $policy['key'],
                'policy_version' => $policy['versions'][0]['version'],
                'document_path' => $policy['versions'][0]['document_path'],
                'content_digest' => $policy['versions'][0]['content_digest'],
            ],
            array_slice($policies['policies'], 0, 2),
        ),
        'ratified_at' => '2026-08-01T10:05:00+08:00',
        'recorded_by_identity_key' => 'angelica-santos',
        'evidence_record_key' => 'EVD-FORMATION-RATIFICATION',
    ]];

    return compact('bootstrap', 'partnership', 'policies');
}

/** @param array{bootstrap: array<string, mixed>, partnership: array<string, mixed>, policies: array<string, mixed>} $fixture */
function resolveFormationBootstrapFixture(array $fixture): ResolvedFormationBootstrap
{
    return (new ResolveFormationBootstrap)->handle(
        FormationBootstrapDefinition::fromArray($fixture['bootstrap']),
        (new ResolvePartnership)->handle(PartnershipDefinition::fromArray($fixture['partnership'])),
        PolicyRegistryDefinition::fromArray($fixture['policies']),
        new DateTimeImmutable('2026-08-20T12:00:00+08:00'),
    );
}

test('it exposes unresolved canonical formation without inventing ratification', function () {
    $resolved = resolveFormationBootstrapFixture([
        'bootstrap' => formationBootstrapArray(),
        'partnership' => formationBootstrapPartnershipArray(),
        'policies' => formationBootstrapPolicyArray(),
    ])->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['ratifications'])->toBe(0)
        ->and($resolved['counts']['policy_approval_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['formation_gaps'], 'code'))
        ->toContain('formation_effective_date_unresolved', 'formation_ratification_not_recorded')
        ->and(array_column($resolved['reports']['consent_gaps'], 'code'))
        ->toContain('formation_consent_rule_unresolved')
        ->and(array_column($resolved['reports']['counsel_review'], 'code'))
        ->toContain('formation_consent_rule_counsel_review');
});

test('it verifies unanimous evidenced formation and emits only allowlisted approval bases', function () {
    $resolved = resolveFormationBootstrapFixture(validFormationBootstrapFixture())->toArray();

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['ratifications'])->toBe(1)
        ->and($resolved['counts']['policy_approval_bases'])->toBe(2)
        ->and(array_column($resolved['policy_approval_bases'], 'target_key'))->toBe([
            'policy:partnership-governance:0.1',
            'policy:authority-and-delegation:0.1',
        ]);
});

test('it refuses ratification when a Founding Partner consent is absent', function () {
    $fixture = validFormationBootstrapFixture();
    array_pop($fixture['bootstrap']['ratification_records'][0]['founding_partner_consents']);

    $resolved = resolveFormationBootstrapFixture($fixture)->toArray();

    expect($resolved['counts']['ratifications'])->toBe(0)
        ->and($resolved['counts']['policy_approval_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['consent_gaps'], 'code'))
        ->toContain('incomplete_unanimous_founder_consent');
});

test('it refuses ratification until the consent mechanism is confirmed by counsel', function () {
    $fixture = validFormationBootstrapFixture();
    $fixture['bootstrap']['consent_rule']['legal_state'] = 'counsel_review';
    $fixture['bootstrap']['consent_rule']['counsel_confirmation_reference'] = null;

    $resolved = resolveFormationBootstrapFixture($fixture)->toArray();

    expect($resolved['counts']['ratifications'])->toBe(0)
        ->and($resolved['counts']['policy_approval_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['counsel_review'], 'code'))
        ->toContain('formation_consent_rule_counsel_review');
});

test('it rejects policy content outside the exact bootstrap allowlist', function () {
    $fixture = validFormationBootstrapFixture();
    $fixture['bootstrap']['ratification_records'][0]['initial_policy_approvals'][] = [
        'policy_key' => 'client-acceptance',
        'policy_version' => '0.1',
        'document_path' => $fixture['policies']['policies'][2]['versions'][0]['document_path'],
        'content_digest' => $fixture['policies']['policies'][2]['versions'][0]['content_digest'],
    ];

    $resolved = resolveFormationBootstrapFixture($fixture)->toArray();

    expect($resolved['counts']['policy_approval_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('invalid_bootstrap_policy_approval');
});

test('it refuses configuration that expands the constitutional bootstrap allowlist', function () {
    $fixture = validFormationBootstrapFixture();
    $fixture['bootstrap']['eligible_policy_versions'][] = [
        'policy_key' => 'client-acceptance',
        'policy_version' => '0.1',
    ];
    $fixture['bootstrap']['ratification_records'][0]['initial_policy_approvals'][] = [
        'policy_key' => 'client-acceptance',
        'policy_version' => '0.1',
        'document_path' => $fixture['policies']['policies'][2]['versions'][0]['document_path'],
        'content_digest' => $fixture['policies']['policies'][2]['versions'][0]['content_digest'],
    ];

    $resolved = resolveFormationBootstrapFixture($fixture)->toArray();

    expect($resolved['counts']['policy_approval_bases'])->toBe(0)
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('invalid_bootstrap_policy_allowlist');
});

test('formation approval still requires separate policy publication and activation', function () {
    $fixture = validFormationBootstrapFixture();
    $bootstrap = resolveFormationBootstrapFixture($fixture);
    $draftRegistry = (new ResolvePolicyRegistry)->handle(
        PolicyRegistryDefinition::fromArray($fixture['policies']),
        new DateTimeImmutable('2026-08-20T12:00:00+08:00'),
        formationBootstrap: $bootstrap,
    )->toArray();

    expect($draftRegistry['policies'][0]['current']['operative'])->toBeFalse()
        ->and($draftRegistry['counts']['publications'])->toBe(0)
        ->and($draftRegistry['counts']['activations'])->toBe(0);
});

test('an exact formation basis can support later publication and activation', function () {
    $fixture = validFormationBootstrapFixture();
    $bootstrap = resolveFormationBootstrapFixture($fixture);
    $registry = $fixture['policies'];
    $version = &$registry['policies'][0]['versions'][0];
    $version['status'] = 'effective';
    $version['effective_at'] = '2026-08-15T00:00:00+08:00';
    $version['formation_ratification_key'] = 'FORMATION-RATIFICATION-0001';
    $version['publication_record_key'] = 'POL-PUBLICATION-BOOTSTRAP-0001';
    $version['activation_record_key'] = 'POL-ACTIVATION-BOOTSTRAP-0001';
    foreach ([
        ['EVD-POL-PUBLICATION-BOOTSTRAP', 'Initial policy publication'],
        ['EVD-POL-ACTIVATION-BOOTSTRAP', 'Initial policy activation'],
    ] as [$key, $subject]) {
        $registry['evidence_records'][] = formationBootstrapEvidence($key, $subject);
    }
    $registry['policy_publication_records'][] = [
        'key' => 'POL-PUBLICATION-BOOTSTRAP-0001',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'document_path' => $version['document_path'],
        'content_digest' => $version['content_digest'],
        'published_by_identity_key' => 'angelica-santos',
        'published_at' => '2026-08-01T11:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-PUBLICATION-BOOTSTRAP',
    ];
    $registry['policy_activation_records'][] = [
        'key' => 'POL-ACTIVATION-BOOTSTRAP-0001',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'formation_ratification_key' => 'FORMATION-RATIFICATION-0001',
        'publication_record_key' => 'POL-PUBLICATION-BOOTSTRAP-0001',
        'effective_at' => '2026-08-15T00:00:00+08:00',
        'activated_by_identity_key' => 'angelica-santos',
        'recorded_at' => '2026-08-01T12:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-ACTIVATION-BOOTSTRAP',
    ];

    $resolved = (new ResolvePolicyRegistry)->handle(
        PolicyRegistryDefinition::fromArray($registry),
        new DateTimeImmutable('2026-08-20T12:00:00+08:00'),
        formationBootstrap: $bootstrap,
    )->toArray();

    expect($resolved['policies'][0]['current'])
        ->approval_admitted->toBeFalse()
        ->formation_ratified->toBeTrue()
        ->publication_verified->toBeTrue()
        ->activation_verified->toBeTrue()
        ->operative->toBeTrue();
});
