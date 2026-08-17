<?php

use App\ClientAcceptance\ClientAcceptanceDefinition;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\Engagements\EngagementDefinition;
use App\Engagements\ResolveEngagements;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvePolicyRegistry;

function engagementDefinitionArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/engagements.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function engagementPolicyRegistryArray(bool $effective = false): array
{
    $registry = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! $effective) {
        return $registry;
    }

    foreach ($registry['policies'] as &$policy) {
        if (! in_array($policy['key'], ['client-acceptance', 'engagement', 'authority-and-delegation'], true)) {
            continue;
        }

        $policy['versions'][0]['status'] = 'effective';
        $policy['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
        $policy['versions'][0]['approval'] = [
            'key' => 'APR-'.strtoupper($policy['key']),
            'outcome' => 'approved',
            'approver' => 'Hypothetical authorized approver',
            'authority_basis' => 'Hypothetical test authority',
            'decided_at' => '2026-07-30T09:00:00+08:00',
            'evidence_record_key' => 'EVD-HYPOTHETICAL-POLICY',
        ];
    }
    unset($policy);

    $registry['evidence_records'][] = [
        'key' => 'EVD-HYPOTHETICAL-POLICY',
        'record_type' => 'Policy Approval',
        'subject' => 'Hypothetical approvals for test policy versions',
        'actor' => 'Hypothetical authorized approver',
        'recorded_at' => '2026-07-30T09:05:00+08:00',
        'source' => 'Hypothetical policy decision record',
        'reason' => 'Supports test policy approvals',
        'state' => 'final',
    ];

    return $registry;
}

function engagementAcceptedClientDefinition(): array
{
    $definition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/client-acceptance.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $definition['evidence_records'] = [
        [
            'key' => 'EVD-HYPOTHETICAL-CLIENT-REVIEW',
            'record_type' => 'Client Acceptance Review',
            'subject' => 'Hypothetical Rural Bank review',
            'actor' => 'Hypothetical review team',
            'recorded_at' => '2026-08-09T16:00:00+08:00',
            'source' => 'Hypothetical review packet',
            'reason' => 'Supports the test acceptance review',
            'state' => 'final',
        ],
        [
            'key' => 'EVD-HYPOTHETICAL-CLIENT-DECISION',
            'record_type' => 'Client Acceptance Record',
            'subject' => 'Hypothetical Rural Bank acceptance',
            'actor' => 'Hypothetical acceptance authority',
            'recorded_at' => '2026-08-10T09:05:00+08:00',
            'source' => 'Hypothetical decision record',
            'reason' => 'Proves the test acceptance decision',
            'state' => 'final',
        ],
    ];
    $definition['prospective_clients'] = [[
        'key' => 'hypothetical-rural-bank',
        'legal_name' => 'Hypothetical Rural Bank, Inc.',
        'jurisdiction' => 'Philippines',
        'entity_type' => 'Bank',
        'proposed_scope' => 'Hypothetical managed cloud operations',
        'review_status' => 'decision_recorded',
        'reviewers' => ['Hypothetical reviewer'],
        'related_parties' => [],
        'assessments' => array_map(
            static fn (array $required): array => [
                'key' => $required['key'],
                'status' => 'satisfactory',
                'summary' => "Hypothetical {$required['label']} assessment completed.",
                'evidence_record_keys' => ['EVD-HYPOTHETICAL-CLIENT-REVIEW'],
            ],
            $definition['required_assessments'],
        ),
        'decision' => [
            'outcome' => 'accepted',
            'reason' => 'Hypothetical test acceptance.',
            'risk_classification' => 'Moderate',
            'conditions' => [],
            'decision_maker' => 'Hypothetical acceptance authority',
            'authority_basis' => 'Hypothetical delegated authority',
            'decided_at' => '2026-08-10T09:00:00+08:00',
            'valid_until' => '2027-02-10T23:59:59+08:00',
            'evidence_record_key' => 'EVD-HYPOTHETICAL-CLIENT-DECISION',
        ],
    ]];

    return $definition;
}

function completeHypotheticalEngagement(): array
{
    return [
        'key' => 'ENG-HYPOTHETICAL-0001',
        'title' => 'Hypothetical Managed Cloud Operations 2027',
        'client_key' => 'hypothetical-rural-bank',
        'lifecycle_status' => 'open',
        'responsible_partner_assignments' => [[
            'partner_key' => 'angelica-santos',
            'effective_from' => '2026-08-10T09:00:00+08:00',
            'effective_until' => null,
            'reason' => 'Initial Engagement responsibility',
            'evidence_record_key' => 'EVD-HYPOTHETICAL-RESPONSIBILITY',
        ]],
        'scope' => [
            'purpose' => 'Operate the hypothetical Client cloud environment.',
            'services' => ['Managed cloud operations'],
            'deliverables' => ['Monthly service report'],
            'exclusions' => ['Application feature development'],
        ],
        'roles' => [
            'originating_partner' => 'lester-hurtado',
            'relationship_partner' => 'lester-hurtado',
            'engagement_partner' => 'angelica-santos',
            'technical_lead' => 'Hypothetical Technical Lead',
            'service_team' => ['Hypothetical Engineer'],
            'practice' => 'Cloud & Infrastructure Practice',
        ],
        'client_mandate' => [
            'grantor' => 'Hypothetical Client Authorized Representative',
            'authority_basis' => 'Hypothetical signed statement of work',
            'granted_at' => '2026-08-11T09:00:00+08:00',
            'valid_until' => '2027-08-11T23:59:59+08:00',
            'authorized_requestors' => ['Hypothetical Client Requestor'],
            'environments' => ['Production'],
            'systems' => ['Hypothetical Cloud Account'],
            'permitted_actions' => ['Operate approved infrastructure within change policy'],
            'prohibited_actions' => ['Change Client-owned billing relationships'],
            'evidence_record_key' => 'EVD-HYPOTHETICAL-MANDATE',
        ],
        'commercial_terms' => [
            'pricing_basis' => 'Hypothetical fixed monthly fee',
            'billing_basis' => 'Monthly in arrears',
            'currency' => 'PHP',
            'discounts' => 'None',
            'liability_position' => 'Subject to hypothetical executed agreement',
        ],
        'risk' => [
            'classification' => 'Moderate',
            'summary' => 'Hypothetical operational risk accepted within defined controls.',
            'owner' => 'angelica-santos',
            'accepted_by' => 'Hypothetical Engagement Authority',
            'authority_basis' => 'Hypothetical delegated risk acceptance authority',
            'accepted_at' => '2026-08-12T08:30:00+08:00',
            'evidence_record_key' => 'EVD-HYPOTHETICAL-RISK',
        ],
        'term' => [
            'commencement_at' => '2026-08-13T10:00:00+08:00',
            'end_at' => '2027-08-12T23:59:59+08:00',
            'termination' => 'Subject to hypothetical executed agreement',
            'transition' => 'Controlled return of access, assets, and records',
        ],
        'operating_terms' => [
            'client_responsibilities' => ['Own accounts, credentials, data, and provider billing'],
            'firm_responsibilities' => ['Operate only within approved scope and authority'],
            'service_levels' => ['Hypothetical service level schedule'],
            'data_classification' => 'Client Confidential',
            'asset_ownership' => ['Cloud account: Client', 'Operational records: as contractually allocated'],
            'approved_access' => ['Named-user least-privilege access subject to separate grants'],
            'change_authority' => 'Specific changes require authority under Change Management Policy',
            'incident_authority' => 'Incident action and communication follow Incident Management Policy',
            'escalation_contacts' => ['Hypothetical Client Contact', 'Responsible Partner'],
            'providers' => ['Hypothetical Cloud Provider'],
            'dependencies' => ['Client connectivity'],
            'insurance_requirements' => ['Subject to professional review'],
            'evidence_requirements' => ['Preserve access, change, incident, and service evidence'],
        ],
        'approval' => [
            'outcome' => 'approved',
            'conditions' => [],
            'approver' => 'Hypothetical Engagement Authority',
            'authority_basis' => 'Hypothetical delegated engagement acceptance authority',
            'decided_at' => '2026-08-12T09:00:00+08:00',
            'evidence_record_key' => 'EVD-HYPOTHETICAL-APPROVAL',
        ],
        'opening' => [
            'opened_by' => 'Hypothetical Opening Officer',
            'authority_basis' => 'Approved Engagement and opening checklist',
            'opened_at' => '2026-08-13T09:00:00+08:00',
            'verification' => ['Accepted Client confirmed', 'Responsible Partner confirmed', 'Mandate confirmed', 'Risk acceptance confirmed'],
            'evidence_record_key' => 'EVD-HYPOTHETICAL-OPENING',
        ],
    ];
}

function engagementWithEvidence(): array
{
    $definition = engagementDefinitionArray();
    $definition['engagements'][] = completeHypotheticalEngagement();

    foreach ([
        ['RESPONSIBILITY', 'Responsible Partner Assignment'],
        ['RISK', 'Risk Acceptance'],
        ['MANDATE', 'Client Mandate'],
        ['APPROVAL', 'Engagement Approval'],
        ['OPENING', 'Engagement Opening Record'],
    ] as [$key, $type]) {
        $definition['evidence_records'][] = [
            'key' => "EVD-HYPOTHETICAL-{$key}",
            'record_type' => $type,
            'subject' => 'Hypothetical Managed Cloud Operations 2027',
            'actor' => 'Hypothetical authorized actor',
            'recorded_at' => '2026-08-13T09:05:00+08:00',
            'source' => 'Hypothetical institutional record',
            'reason' => "Supports {$type}",
            'state' => 'final',
        ];
    }

    return $definition;
}

function resolveHypotheticalEngagement(array $definition, bool $effectivePolicies = true, ?array $clientDefinition = null): array
{
    $policies = PolicyRegistryDefinition::fromArray(engagementPolicyRegistryArray($effectivePolicies));
    $clientAcceptance = (new ResolveClientAcceptance)->handle(
        ClientAcceptanceDefinition::fromArray($clientDefinition ?? engagementAcceptedClientDefinition()),
        $policies,
        new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
    );
    $partnershipDefinition = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/partnership.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    return (new ResolveEngagements)->handle(
        EngagementDefinition::fromArray($definition),
        $clientAcceptance,
        (new ResolvePartnership)->handle(PartnershipDefinition::fromArray($partnershipDefinition)),
        (new ResolvePolicyRegistry)->handle(
            $policies,
            new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
        ),
        new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
    )->toArray();
}

test('it exposes that the canonical Engagement Opening control is not operative', function () {
    $resolved = resolveHypotheticalEngagement(engagementDefinitionArray(), effectivePolicies: false);

    expect($resolved)
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved['counts']['engagements'])->toBe(0)
        ->and($resolved['opening_requirements'])->toHaveCount(10)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(2)
        ->and(array_column($resolved['reports']['readiness_gaps'], 'code'))
        ->each->toBe('engagement_governing_policy_not_effective');
});

test('it opens a fully resolved Engagement and permits Client work', function () {
    $resolved = resolveHypotheticalEngagement(engagementWithEvidence());

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['open_for_client_work'])->toBe(1)
        ->and($resolved['engagements'][0]['operational_status'])->toBe('open_engagement')
        ->and($resolved['engagements'][0]['responsible_partner']['partner_name'])->toBe('Angelica Anaïs C. Santos')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeTrue()
        ->and($resolved['reports']['conflicts'])->toBe([])
        ->and($resolved['reports']['decision_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);
});

test('it refuses to infer Client acceptance from an Engagement record', function () {
    $clientDefinition = engagementAcceptedClientDefinition();
    $clientDefinition['prospective_clients'][0]['review_status'] = 'under_review';
    $clientDefinition['prospective_clients'][0]['decision'] = null;
    $resolved = resolveHypotheticalEngagement(engagementWithEvidence(), clientDefinition: $clientDefinition);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('client_not_accepted_for_engagement')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse()
        ->and($resolved['engagements'][0]['operational_status'])->toBe('blocked_opening');
});

test('it requires exactly one current Responsible Partner', function () {
    $definition = engagementWithEvidence();
    $definition['engagements'][0]['responsible_partner_assignments'][] = [
        'partner_key' => 'lester-hurtado',
        'effective_from' => '2026-08-10T09:00:00+08:00',
        'effective_until' => null,
        'evidence_record_key' => 'EVD-HYPOTHETICAL-RESPONSIBILITY',
    ];
    $resolved = resolveHypotheticalEngagement($definition);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('responsible_partner_not_singular')
        ->and($resolved['engagements'][0]['responsible_partner'])->toBeNull()
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse();
});

test('it requires a current evidenced Client Mandate with explicit boundaries', function () {
    $definition = engagementWithEvidence();
    $definition['engagements'][0]['client_mandate']['permitted_actions'] = [];
    $definition['engagements'][0]['client_mandate']['valid_until'] = '2026-08-16T23:59:59+08:00';
    $definition['engagements'][0]['client_mandate']['evidence_record_key'] = 'UNKNOWN';
    $resolved = resolveHypotheticalEngagement($definition);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('incomplete_client_mandate')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('missing_client_mandate_evidence')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse();
});

test('it requires explicit evidenced Engagement risk acceptance', function () {
    $definition = engagementWithEvidence();
    $definition['engagements'][0]['risk']['accepted_by'] = null;
    $definition['engagements'][0]['risk']['evidence_record_key'] = 'UNKNOWN';
    $resolved = resolveHypotheticalEngagement($definition);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('incomplete_engagement_risk')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('missing_engagement_risk_evidence')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse();
});

test('it refuses incomplete Evidence Records and expired Engagement terms', function () {
    $definition = engagementWithEvidence();
    $definition['evidence_records'][0]['actor'] = null;
    $definition['engagements'][0]['term']['end_at'] = '2026-08-16T23:59:59+08:00';
    $resolved = resolveHypotheticalEngagement($definition);

    expect(array_column($resolved['reports']['evidence_gaps'], 'code'))
        ->toContain('incomplete_engagement_evidence_record', 'missing_responsible_partner_evidence')
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('engagement_term_not_current')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse();
});

test('it preserves approval as distinct from opening', function () {
    $definition = engagementWithEvidence();
    $definition['engagements'][0]['lifecycle_status'] = 'approved';
    $definition['engagements'][0]['opening'] = null;
    $resolved = resolveHypotheticalEngagement($definition);

    expect($resolved['engagements'][0])
        ->operational_status->toBe('approved_not_open')
        ->may_perform_client_work->toBeFalse()
        ->and($resolved['counts']['open_for_client_work'])->toBe(0);
});

test('it never infers approval from an Opening Record', function () {
    $definition = engagementWithEvidence();
    $definition['engagements'][0]['approval'] = null;
    $resolved = resolveHypotheticalEngagement($definition);

    expect(array_column($resolved['reports']['decision_gaps'], 'code'))
        ->toContain('missing_engagement_approval')
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('engagement_opened_before_approval', 'open_engagement_without_complete_gate')
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse();
});

test('it blocks opening while required policies are not Effective', function () {
    $resolved = resolveHypotheticalEngagement(engagementWithEvidence(), effectivePolicies: false);

    expect($resolved['reports']['readiness_gaps'])->toHaveCount(2)
        ->and($resolved['engagements'][0]['may_perform_client_work'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('open_engagement_without_complete_gate');
});
