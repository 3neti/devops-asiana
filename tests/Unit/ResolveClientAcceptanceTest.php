<?php

use App\ClientAcceptance\ClientAcceptanceDefinition;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\Policies\PolicyRegistryDefinition;

function clientAcceptanceArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/client-acceptance.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function acceptancePolicyRegistryArray(bool $effective = false): array
{
    $registry = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! $effective) {
        return $registry;
    }

    $policyIndex = array_search('client-acceptance', array_column($registry['policies'], 'key'), true);
    $registry['policies'][$policyIndex]['versions'][0]['status'] = 'effective';
    $registry['policies'][$policyIndex]['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
    $registry['policies'][$policyIndex]['versions'][0]['approval'] = [
        'key' => 'APR-CLIENT-ACCEPTANCE-0001',
        'outcome' => 'approved',
        'approver' => 'Founding Partners',
        'authority_basis' => 'Recorded Partner decision',
        'decided_at' => '2026-07-30T09:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-CLIENT-ACCEPTANCE-0001',
    ];

    return $registry;
}

function prospectiveClientRecord(): array
{
    $definition = clientAcceptanceArray();

    return [
        'key' => 'hypothetical-rural-bank',
        'legal_name' => 'Hypothetical Rural Bank, Inc.',
        'display_name' => 'Hypothetical Rural Bank',
        'jurisdiction' => 'Philippines',
        'entity_type' => 'Bank',
        'registration_identifier' => 'TEST-ONLY',
        'proposed_scope' => 'Hypothetical managed cloud operations assessment',
        'review_status' => 'decision_recorded',
        'reviewers' => ['Acceptance Reviewer A', 'Acceptance Reviewer B'],
        'related_parties' => [
            [
                'party' => 'ODTI',
                'relationship' => 'Platform ecosystem provider',
                'disclosed' => true,
                'disposition' => 'Disclosed and independently assessed',
            ],
        ],
        'assessments' => array_map(
            static fn (array $required): array => [
                'key' => $required['key'],
                'status' => 'satisfactory',
                'summary' => "Hypothetical review completed for {$required['label']}.",
                'disposition' => null,
                'risk_owner' => null,
                'evidence_record_keys' => ['EVD-CAR-REVIEW-0001'],
            ],
            $definition['required_assessments'],
        ),
        'decision' => [
            'outcome' => 'accepted',
            'reason' => 'Hypothetical test record satisfies the review standard.',
            'risk_classification' => 'Moderate',
            'conditions' => [],
            'decision_maker' => 'Acceptance Authority A',
            'authority_basis' => 'Hypothetical delegated Client Acceptance authority',
            'decided_at' => '2026-08-10T09:00:00+08:00',
            'valid_until' => '2027-02-10T23:59:59+08:00',
            'evidence_record_key' => 'EVD-CAR-DECISION-0001',
        ],
    ];
}

function acceptanceWithEvidence(): array
{
    $definition = clientAcceptanceArray();
    $definition['evidence_records'] = [
        [
            'key' => 'EVD-CAR-REVIEW-0001',
            'record_type' => 'Client Acceptance Review',
            'subject' => 'Hypothetical Rural Bank review',
            'actor' => 'Acceptance Review Team',
            'recorded_at' => '2026-08-09T16:00:00+08:00',
            'source' => 'Hypothetical review packet',
            'reason' => 'Supports required acceptance assessments',
            'state' => 'final',
        ],
        [
            'key' => 'EVD-CAR-DECISION-0001',
            'record_type' => 'Client Acceptance Record',
            'subject' => 'Hypothetical Rural Bank decision',
            'actor' => 'Acceptance Authority A',
            'recorded_at' => '2026-08-10T09:05:00+08:00',
            'source' => 'Hypothetical decision record',
            'reason' => 'Proves explicit Client Acceptance decision',
            'state' => 'final',
        ],
    ];
    $definition['prospective_clients'][] = prospectiveClientRecord();

    return $definition;
}

function resolveAcceptance(array $definition, bool $effectivePolicy = false): array
{
    return (new ResolveClientAcceptance)
        ->handle(
            ClientAcceptanceDefinition::fromArray($definition),
            PolicyRegistryDefinition::fromArray(acceptancePolicyRegistryArray($effectivePolicy)),
            new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
        )
        ->toArray();
}

test('it exposes that the canonical acceptance control is not yet operative', function () {
    $resolved = resolveAcceptance(clientAcceptanceArray());

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['governing_policy']['status'])->toBe('draft')
        ->and($resolved['governing_policy']['operative'])->toBeFalse()
        ->and($resolved['counts']['prospective_clients'])->toBe(0)
        ->and($resolved['required_assessments'])->toHaveCount(16)
        ->and(array_column($resolved['reports']['readiness_gaps'], 'code'))
        ->toContain('governing_policy_not_effective');
});

test('it resolves an explicit evidenced acceptance under an effective policy', function () {
    $resolved = resolveAcceptance(acceptanceWithEvidence(), effectivePolicy: true);

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['by_outcome']['accepted'])->toBe(1)
        ->and($resolved['prospective_clients'][0]['institutional_status'])->toBe('accepted_client')
        ->and($resolved['prospective_clients'][0]['decision']['permits_engagement_consideration'])->toBeTrue()
        ->and($resolved['reports']['conflicts'])->toBe([])
        ->and($resolved['reports']['decision_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);
});

test('it never infers acceptance from review activity or an engagement reference', function () {
    $definition = acceptanceWithEvidence();
    $definition['prospective_clients'][0]['review_status'] = 'under_review';
    $definition['prospective_clients'][0]['decision'] = null;
    $definition['prospective_clients'][0]['engagement_reference'] = 'ENG-HYPOTHETICAL-0001';

    $resolved = resolveAcceptance($definition, effectivePolicy: true);

    expect($resolved['prospective_clients'][0])
        ->institutional_status->toBe('prospective_client')
        ->decision->toBeNull()
        ->and($resolved['counts']['by_outcome']['accepted'])->toBe(0);
});

test('it blocks acceptance with unresolved assessments or an inoperative policy', function () {
    $definition = acceptanceWithEvidence();
    $definition['prospective_clients'][0]['assessments'][0]['status'] = 'unresolved';

    $resolved = resolveAcceptance($definition);

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('decision_under_inoperative_policy', 'acceptance_with_unresolved_assessment')
        ->and($resolved['prospective_clients'][0]['decision']['permits_engagement_consideration'])->toBeFalse()
        ->and($resolved['prospective_clients'][0]['institutional_status'])->toBe('prospective_client');
});

test('it requires conditional acceptance terms and current validity', function () {
    $definition = acceptanceWithEvidence();
    $definition['prospective_clients'][0]['decision']['outcome'] = 'accepted_with_conditions';
    $definition['prospective_clients'][0]['decision']['conditions'] = [];
    $definition['prospective_clients'][0]['decision']['valid_until'] = '2026-08-16T23:59:59+08:00';

    $resolved = resolveAcceptance($definition, effectivePolicy: true);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('missing_acceptance_conditions')
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('accepted_client_past_validity')
        ->and($resolved['prospective_clients'][0]['decision']['permits_engagement_consideration'])->toBeFalse()
        ->and($resolved['prospective_clients'][0]['institutional_status'])->toBe('acceptance_expired');
});

test('it requires a rejection reason and explicit decision evidence', function () {
    $definition = acceptanceWithEvidence();
    $definition['prospective_clients'][0]['decision']['outcome'] = 'rejected';
    $definition['prospective_clients'][0]['decision']['reason'] = null;
    $definition['prospective_clients'][0]['decision']['evidence_record_key'] = null;

    $resolved = resolveAcceptance($definition, effectivePolicy: true);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('missing_rejection_reason')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('missing_acceptance_decision_evidence')
        ->and($resolved['prospective_clients'][0]['institutional_status'])->toBe('prospective_client');
});
