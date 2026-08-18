<?php

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\GovernanceMeetings\GovernanceMeetingDefinition;
use App\GovernanceMeetings\ResolveGovernanceMeetings;
use App\Partnership\PartnershipDefinition;
use App\Partnership\ResolvedPartnership;
use App\Partnership\ResolvePartnership;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;

function governanceMeetingDefinition(): GovernanceMeetingDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/governance-meetings.json'), true, flags: JSON_THROW_ON_ERROR);

    return GovernanceMeetingDefinition::fromArray($definition);
}

function governanceMeetingPartnership(): ResolvedPartnership
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/partnership.json'), true, flags: JSON_THROW_ON_ERROR);

    return (new ResolvePartnership)->handle(PartnershipDefinition::fromArray($definition));
}

function governanceMeetingPolicies(string $status = 'effective'): ResolvedPolicyRegistry
{
    $policies = array_map(static fn (array $policy): array => [
        'key' => $policy['key'],
        'current_status' => $status,
        'current_status_label' => $status === 'effective' ? 'Effective' : 'Draft',
        'current' => [
            'version' => $policy['version'],
            'status' => $status,
            'effective_at' => $status === 'effective' ? '2026-01-01T00:00:00+08:00' : null,
        ],
    ], governanceMeetingDefinition()->governingPolicies);

    return new ResolvedPolicyRegistry(1, $policies, [], [], [], [], [], []);
}

function governanceMeetingAuthority(bool $effective = true): ResolvedAuthorityMatrix
{
    return new ResolvedAuthorityMatrix(
        1,
        ['operative' => $effective],
        [],
        [[
            'key' => 'founding-partner-reserved-matter-participation',
            'action_label' => 'Participate in a Reserved Matter decision',
            'grants_firm_authority' => $effective,
            'effective_holder_keys' => $effective ? ['lester-hurtado', 'angelica-santos'] : [],
            'effective_holder_names' => $effective ? ['Lester B. Hurtado', 'Angelica Anaïs C. Santos'] : [],
        ]],
        [],
        [],
        ['active' => $effective ? 1 : 0],
        ['effective' => $effective ? 1 : 0],
        [],
        [],
        [],
        [],
        [],
    );
}

/** @return array<string, mixed> */
function governanceEvidence(string $key, string $type, string $actor): array
{
    return [
        'key' => $key,
        'record_type' => $type,
        'actor' => $actor,
        'occurred_at' => '2026-08-18T10:00:00+08:00',
        'source' => 'Governance Meeting test fixture',
        'reason' => 'Prove one distinct governance fact.',
        'approval' => 'Test fixture only',
        'state' => 'accepted',
        'supporting_evidence' => ['fixture'],
    ];
}

/** @return list<array<string, mixed>> */
function governanceEvidenceSet(): array
{
    return [
        governanceEvidence('evidence-notice', 'Meeting Notice', 'Angelica Anaïs C. Santos'),
        governanceEvidence('evidence-attendance-lester', 'Attendance Record', 'Lester B. Hurtado'),
        governanceEvidence('evidence-attendance-angelica', 'Attendance Record', 'Angelica Anaïs C. Santos'),
        governanceEvidence('evidence-minutes', 'Meeting Minutes', 'Meeting Secretary'),
        governanceEvidence('evidence-proposal', 'Governance Proposal', 'Lester B. Hurtado'),
        governanceEvidence('evidence-disclosure-lester', 'Conflict Declaration', 'Lester B. Hurtado'),
        governanceEvidence('evidence-disclosure-angelica', 'Conflict Declaration', 'Angelica Anaïs C. Santos'),
        governanceEvidence('evidence-vote-lester', 'Partner Vote', 'Lester B. Hurtado'),
        governanceEvidence('evidence-vote-angelica', 'Partner Vote', 'Angelica Anaïs C. Santos'),
        governanceEvidence('evidence-outcome', 'Governance Outcome', 'Meeting Secretary'),
    ];
}

/** @return array<string, mixed> */
function concludedGovernanceMeeting(): array
{
    return [
        'key' => 'meeting-constitutional-001',
        'title' => 'Constitutional Governance Meeting',
        'lifecycle_status' => 'concluded',
        'scheduled_start' => '2026-08-18T08:00:00+08:00',
        'convened_at' => '2026-08-18T09:00:00+08:00',
        'concluded_at' => '2026-08-18T10:00:00+08:00',
        'chair_identity_key' => 'angelica-santos',
        'secretary_identity_key' => 'lester-hurtado',
        'notice' => [
            'issued_at' => '2026-08-11T09:00:00+08:00',
            'issued_by_identity_key' => 'angelica-santos',
            'recipient_identity_keys' => ['lester-hurtado', 'angelica-santos'],
            'agenda_fixed_at' => '2026-08-11T09:00:00+08:00',
            'evidence_record_key' => 'evidence-notice',
        ],
        'attendance' => [
            [
                'identity_key' => 'lester-hurtado',
                'status' => 'present',
                'joined_at' => '2026-08-18T09:00:00+08:00',
                'left_at' => '2026-08-18T10:00:00+08:00',
                'evidence_record_key' => 'evidence-attendance-lester',
            ],
            [
                'identity_key' => 'angelica-santos',
                'status' => 'present',
                'joined_at' => '2026-08-18T09:00:00+08:00',
                'left_at' => '2026-08-18T10:00:00+08:00',
                'evidence_record_key' => 'evidence-attendance-angelica',
            ],
        ],
        'agenda_items' => [[
            'key' => 'item-admit-equity-partner',
            'title' => 'Admission of an Equity Partner',
            'classification' => 'reserved',
            'reserved_matter_key' => 'admit-equity-partners',
            'authority_matrix_entry_key' => 'founding-partner-reserved-matter-participation',
            'proposal' => [
                'proposed_by_identity_key' => 'lester-hurtado',
                'statement' => 'Admit a specifically identified candidate subject to final instruments.',
                'reason' => 'Expand qualified Partnership capacity.',
                'proposed_at' => '2026-08-18T09:05:00+08:00',
                'evidence_record_key' => 'evidence-proposal',
            ],
            'risk' => [
                'classification' => 'high',
                'owner_identity_key' => 'angelica-santos',
                'acceptance_reference' => 'Reserved Matter vote',
            ],
            'disclosures' => [
                [
                    'identity_key' => 'lester-hurtado',
                    'status' => 'no_conflict',
                    'related_party' => false,
                    'recused' => false,
                    'reason' => null,
                    'evidence_record_key' => 'evidence-disclosure-lester',
                ],
                [
                    'identity_key' => 'angelica-santos',
                    'status' => 'no_conflict',
                    'related_party' => false,
                    'recused' => false,
                    'reason' => null,
                    'evidence_record_key' => 'evidence-disclosure-angelica',
                ],
            ],
            'votes' => [
                [
                    'identity_key' => 'lester-hurtado',
                    'choice' => 'for',
                    'cast_at' => '2026-08-18T09:45:00+08:00',
                    'evidence_record_key' => 'evidence-vote-lester',
                ],
                [
                    'identity_key' => 'angelica-santos',
                    'choice' => 'for',
                    'cast_at' => '2026-08-18T09:45:00+08:00',
                    'evidence_record_key' => 'evidence-vote-angelica',
                ],
            ],
            'recorded_outcome' => 'adopted',
            'outcome_recorded_at' => '2026-08-18T09:50:00+08:00',
            'outcome_evidence_record_key' => 'evidence-outcome',
        ]],
        'minutes_evidence_record_key' => 'evidence-minutes',
    ];
}

/** @param array<string, mixed> $changes */
function governanceDefinitionWith(array $changes): GovernanceMeetingDefinition
{
    $definition = governanceMeetingDefinition();
    $rules = $definition->decisionRules;
    foreach (['ordinary', 'reserved'] as $classification) {
        $rules[$classification]['quorum'] = [
            'state' => 'resolved',
            'required_governance_weight' => 100,
        ];
        $rules[$classification]['approval'] = [
            'state' => 'resolved',
            'basis' => 'total_governance_weight',
            'required_governance_weight' => 100,
        ];
    }
    $rules['deadlock'] = [
        'state' => 'resolved',
        'mechanism' => 'Mediation followed by the constitutionally adopted next step',
        'decision_reference' => 'test-only-rule',
        'counsel_review' => true,
    ];

    return new GovernanceMeetingDefinition(
        $definition->schemaVersion,
        $definition->governingPolicies,
        $definition->meetingRequirements,
        $changes['decision_rules'] ?? $rules,
        $definition->reservedMatterCatalog,
        $changes['meetings'] ?? $definition->meetings,
        $changes['evidence'] ?? $definition->evidenceRecords,
    );
}

/** @return array<string, mixed> */
function resolveGovernanceFixture(GovernanceMeetingDefinition $definition, ?ResolvedAuthorityMatrix $authority = null): array
{
    return (new ResolveGovernanceMeetings)->handle(
        $definition,
        governanceMeetingPartnership(),
        governanceMeetingPolicies(),
        $authority ?? governanceMeetingAuthority(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('canonical governance state derives equal Partner weight and preserves unresolved rules', function () {
    $resolved = (new ResolveGovernanceMeetings)->handle(
        governanceMeetingDefinition(),
        governanceMeetingPartnership(),
        governanceMeetingPolicies('draft'),
        governanceMeetingAuthority(false),
    )->toArray();

    expect($resolved['counts']['governing_partners'])->toBe(2)
        ->and($resolved['counts']['governance_weight'])->toBe(100)
        ->and($resolved['governing_partners'][0]['governance_weight'])->toBe(50)
        ->and($resolved['governing_partners'][1]['governance_weight'])->toBe(50)
        ->and($resolved['counts']['meetings'])->toBe(0)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(8);
});

test('a unanimous evidenced Reserved Matter vote emits a candidate without creating a canonical Decision Record', function () {
    $resolved = resolveGovernanceFixture(governanceDefinitionWith([
        'meetings' => [concludedGovernanceMeeting()],
        'evidence' => governanceEvidenceSet(),
    ]));
    $item = $resolved['meeting_records'][0]['agenda_items'][0];

    expect($item['quorum']['met'])->toBeTrue()
        ->and($item['vote_tally'])->toBe(['for' => 100, 'against' => 0, 'abstain' => 0])
        ->and($item['derived_outcome'])->toBe('adopted')
        ->and($item['authority_resolved'])->toBeTrue()
        ->and($item['decision_record_candidate']['canonical_decision_record_created'])->toBeFalse()
        ->and($resolved['counts']['decision_record_candidates'])->toBe(1)
        ->and($resolved['compiler_status'])->toBe('consistent');
});

test('a recused Partner cannot vote on the affected agenda item', function () {
    $meeting = concludedGovernanceMeeting();
    $meeting['agenda_items'][0]['disclosures'][0]['status'] = 'conflict_disclosed';
    $meeting['agenda_items'][0]['disclosures'][0]['recused'] = true;
    $meeting['agenda_items'][0]['disclosures'][0]['reason'] = 'A disclosed related-party interest.';
    $resolved = resolveGovernanceFixture(governanceDefinitionWith([
        'meetings' => [$meeting],
        'evidence' => governanceEvidenceSet(),
    ]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))->toContain('recused_partner_voted')
        ->and($resolved['counts']['decision_record_candidates'])->toBe(0);
});

test('silence is not affirmative consent or an abstention', function () {
    $meeting = concludedGovernanceMeeting();
    array_pop($meeting['agenda_items'][0]['votes']);
    $meeting['agenda_items'][0]['recorded_outcome'] = 'rejected';
    $resolved = resolveGovernanceFixture(governanceDefinitionWith([
        'meetings' => [$meeting],
        'evidence' => governanceEvidenceSet(),
    ]));

    expect(array_column($resolved['reports']['meeting_gaps'], 'code'))->toContain('missing_partner_vote')
        ->and($resolved['meeting_records'][0]['agenda_items'][0]['vote_tally']['for'])->toBe(50)
        ->and($resolved['counts']['decision_record_candidates'])->toBe(0);
});

test('an equal split exposes unresolved deadlock instead of manufacturing an outcome', function () {
    $definition = governanceDefinitionWith([
        'meetings' => [concludedGovernanceMeeting()],
        'evidence' => governanceEvidenceSet(),
    ]);
    $rules = $definition->decisionRules;
    $rules['deadlock'] = governanceMeetingDefinition()->decisionRules['deadlock'];
    $meeting = $definition->meetings[0];
    $meeting['agenda_items'][0]['votes'][1]['choice'] = 'against';
    $meeting['agenda_items'][0]['recorded_outcome'] = 'deadlock_unresolved';
    $resolved = resolveGovernanceFixture(governanceDefinitionWith([
        'decision_rules' => $rules,
        'meetings' => [$meeting],
        'evidence' => governanceEvidenceSet(),
    ]));

    expect($resolved['meeting_records'][0]['agenda_items'][0]['derived_outcome'])->toBe('deadlock_unresolved')
        ->and($resolved['counts']['decision_record_candidates'])->toBe(0)
        ->and(array_column($resolved['reports']['readiness_gaps'], 'code'))->toContain('deadlock_mechanism_unresolved');
});

test('recorded outcome cannot contradict the weighted vote', function () {
    $meeting = concludedGovernanceMeeting();
    $meeting['agenda_items'][0]['recorded_outcome'] = 'rejected';
    $resolved = resolveGovernanceFixture(governanceDefinitionWith([
        'meetings' => [$meeting],
        'evidence' => governanceEvidenceSet(),
    ]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))->toContain('governance_outcome_mismatch')
        ->and($resolved['counts']['decision_record_candidates'])->toBe(0);
});
