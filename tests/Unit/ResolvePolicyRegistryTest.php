<?php

use App\DecisionRecords\ResolvedDecisionRecords;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvePolicyRegistry;

function policyRegistryArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function resolvePolicyRegistry(array $registry, ?ResolvedDecisionRecords $decisionRecords = null): array
{
    return (new ResolvePolicyRegistry)
        ->handle(
            PolicyRegistryDefinition::fromArray($registry),
            new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
            $decisionRecords,
        )
        ->toArray();
}

function policyApprovalDecisionRecords(): ResolvedDecisionRecords
{
    return new ResolvedDecisionRecords(
        schemaVersion: 1,
        governingPolicies: [],
        recordRequirements: [],
        collectiveAdmissions: [],
        availableCollectiveCandidates: [],
        decisions: [[
            'key' => 'decision-approve-partnership-governance-0-1',
            'title' => 'Approve Partnership Governance Policy version 0.1',
            'lifecycle_status' => 'effective',
            'context' => [
                'type' => 'firm_governance',
                'subject' => 'Partnership Governance Policy version 0.1',
                'reference_keys' => ['policy:partnership-governance:0.1'],
            ],
            'decision' => [
                'outcome' => 'approved',
                'decided_at' => '2026-08-01T09:45:00+08:00',
                'effective_at' => '2026-08-01T09:45:00+08:00',
                'evidence_record_key' => 'decision-evidence-policy-approval',
            ],
            'authority_basis_type' => 'collective_governance',
            'institutionally_valid' => true,
            'may_execute' => false,
        ]],
        executions: [],
        verifications: [],
        evidenceRecords: [],
        lifecycleCounts: [],
        conflicts: [],
        authorityGaps: [],
        decisionGaps: [],
        evidenceGaps: [],
        admissionGaps: [],
        readinessGaps: [],
    );
}

/** @return array<string, mixed> */
function policyDecisionSnapshot(): array
{
    $decision = policyApprovalDecisionRecords()->decisions[0];

    return [
        'title' => $decision['title'],
        'context' => $decision['context'],
        'outcome' => $decision['decision']['outcome'],
        'decided_at' => $decision['decision']['decided_at'],
        'effective_at' => $decision['decision']['effective_at'],
        'evidence_record_key' => $decision['decision']['evidence_record_key'],
        'authority_basis_type' => $decision['authority_basis_type'],
    ];
}

function effectivePolicyRegistry(): array
{
    $registry = policyRegistryArray();
    foreach ([
        ['EVD-POL-ADMISSION-0001', 'Policy Approval Admission', 'Admit exact effective Decision Record'],
        ['EVD-POL-PUBLICATION-0001', 'Policy Publication', 'Publish the controlled approved text'],
        ['EVD-POL-ACTIVATION-0001', 'Policy Activation', 'Make the published approved version effective'],
    ] as [$key, $type, $reason]) {
        $registry['evidence_records'][] = [
            'key' => $key,
            'record_type' => $type,
            'subject' => 'Partnership Governance Policy version 0.1',
            'actor' => 'Angelica Anaïs C. Santos',
            'recorded_at' => '2026-08-02T10:00:00+08:00',
            'source' => 'Institutional policy lifecycle record',
            'reason' => $reason,
            'state' => 'final',
        ];
    }
    $registry['policies'][0]['versions'][0]['status'] = 'effective';
    $registry['policies'][0]['versions'][0]['effective_at'] = '2026-08-15T00:00:00+08:00';
    $registry['policies'][0]['versions'][0]['approval_admission_key'] = 'POL-ADMISSION-0001';
    $registry['policies'][0]['versions'][0]['publication_record_key'] = 'POL-PUBLICATION-0001';
    $registry['policies'][0]['versions'][0]['activation_record_key'] = 'POL-ACTIVATION-0001';
    $registry['policy_approval_admission_records'][] = [
        'key' => 'POL-ADMISSION-0001',
        'status' => 'admitted',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'decision_record_key' => 'decision-approve-partnership-governance-0-1',
        'decision_snapshot' => policyDecisionSnapshot(),
        'recorded_by_identity_key' => 'angelica-santos',
        'recorded_at' => '2026-08-01T10:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-ADMISSION-0001',
    ];
    $registry['policy_publication_records'][] = [
        'key' => 'POL-PUBLICATION-0001',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'document_path' => $registry['policies'][0]['versions'][0]['document_path'],
        'content_digest' => $registry['policies'][0]['versions'][0]['content_digest'],
        'published_by_identity_key' => 'angelica-santos',
        'published_at' => '2026-08-02T09:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-PUBLICATION-0001',
    ];
    $registry['policy_activation_records'][] = [
        'key' => 'POL-ACTIVATION-0001',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'approval_admission_key' => 'POL-ADMISSION-0001',
        'publication_record_key' => 'POL-PUBLICATION-0001',
        'effective_at' => '2026-08-15T00:00:00+08:00',
        'activated_by_identity_key' => 'angelica-santos',
        'recorded_at' => '2026-08-02T10:00:00+08:00',
        'evidence_record_key' => 'EVD-POL-ACTIVATION-0001',
    ];

    return $registry;
}

test('it resolves the canonical draft register without inventing approvals', function () {
    $resolved = resolvePolicyRegistry(policyRegistryArray());

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['policies'])->toBe(12)
        ->and($resolved['counts']['by_status']['draft'])->toBe(12)
        ->and($resolved['counts']['by_status']['approved'])->toBe(0)
        ->and($resolved['policies'][0]['current']['content_integrity'])->toBe('mutable_draft')
        ->and($resolved['reports']['conflicts'])->toBe([])
        ->and($resolved['reports']['lifecycle_gaps'])->toBe([]);
});

test('it detects content changed after submission for review', function () {
    $registry = policyRegistryArray();
    $registry['policies'][0]['versions'][0]['status'] = 'under_review';
    $registry['policies'][0]['versions'][0]['content_digest'] = str_repeat('0', 64);

    $resolved = resolvePolicyRegistry($registry);

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('policy_content_changed');
});

test('it never infers policy approval from lifecycle status', function () {
    $registry = policyRegistryArray();
    $registry['policies'][0]['versions'][0]['status'] = 'approved';

    $resolved = resolvePolicyRegistry($registry);

    expect(array_column($resolved['reports']['lifecycle_gaps'], 'code'))
        ->toContain('missing_policy_approval')
        ->and($resolved['counts']['by_status']['approved'])->toBe(1);
});

test('it never infers approval from an eligible effective Decision Record', function () {
    $registry = policyRegistryArray();
    $registry['policies'][0]['versions'][0]['status'] = 'approved';

    $resolved = resolvePolicyRegistry($registry, policyApprovalDecisionRecords());

    expect($resolved['counts']['available_decision_candidates'])->toBe(1)
        ->and($resolved['counts']['approval_admissions'])->toBe(0)
        ->and($resolved['policies'][0]['current']['approval_admitted'])->toBeFalse()
        ->and(array_column($resolved['reports']['lifecycle_gaps'], 'code'))->toContain('missing_policy_approval');
});

test('it accepts an effective policy only with separate approval and evidence', function () {
    $resolved = resolvePolicyRegistry(effectivePolicyRegistry(), policyApprovalDecisionRecords());

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['by_status']['effective'])->toBe(1)
        ->and($resolved['policies'][0]['current']['content_integrity'])->toBe('verified')
        ->and($resolved['policies'][0]['current']['approval_admitted'])->toBeTrue()
        ->and($resolved['policies'][0]['current']['publication_verified'])->toBeTrue()
        ->and($resolved['policies'][0]['current']['activation_verified'])->toBeTrue()
        ->and($resolved['policies'][0]['current']['operative'])->toBeTrue()
        ->and($resolved['counts']['approval_admissions'])->toBe(1)
        ->and($resolved['counts']['publications'])->toBe(1)
        ->and($resolved['counts']['activations'])->toBe(1)
        ->and($resolved['reports']['lifecycle_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);
});

test('it rejects a Policy Approval Admission that contradicts its Decision Record', function () {
    $registry = effectivePolicyRegistry();
    $registry['policy_approval_admission_records'][0]['decision_snapshot']['outcome'] = 'rejected';

    $resolved = resolvePolicyRegistry($registry, policyApprovalDecisionRecords());

    expect($resolved['policy_approval_admission_records'][0]['grants_policy_approval_basis'])->toBeFalse()
        ->and($resolved['policy_activation_records'][0]['activation_verified'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain('policy_decision_snapshot_mismatch')
        ->and(array_column($resolved['reports']['activation_gaps'], 'code'))->toContain('activation_without_admitted_approval');
});

test('it never infers activation from approval and publication', function () {
    $registry = effectivePolicyRegistry();
    $registry['policy_activation_records'] = [];
    $registry['policies'][0]['versions'][0]['activation_record_key'] = null;

    $resolved = resolvePolicyRegistry($registry, policyApprovalDecisionRecords());

    expect($resolved['counts']['approval_admissions'])->toBe(1)
        ->and($resolved['counts']['publications'])->toBe(1)
        ->and($resolved['counts']['activations'])->toBe(0)
        ->and($resolved['policies'][0]['current']['operative'])->toBeFalse()
        ->and(array_column($resolved['reports']['lifecycle_gaps'], 'code'))->toContain('missing_policy_activation');
});

test('it requires an exception to have its own approval evidence and valid term', function () {
    $registry = effectivePolicyRegistry();
    $registry['evidence_records'][] = [
        'key' => 'EVD-EXC-0001',
        'record_type' => 'Policy Exception Approval',
        'subject' => 'EXC-0001',
        'actor' => 'Managing Partner',
        'recorded_at' => '2026-08-10T10:00:00+08:00',
        'source' => 'Exception decision record',
        'reason' => 'Evidence of explicit exception approval',
        'state' => 'final',
    ];
    $registry['exceptions'][] = [
        'key' => 'EXC-0001',
        'policy_key' => 'partnership-governance',
        'policy_version' => '0.1',
        'specific_requirement' => 'Illustrative test requirement',
        'reason' => 'Illustrative test reason',
        'risk' => 'Illustrative test risk',
        'compensating_controls' => ['Independent review'],
        'status' => 'active',
        'effective_at' => '2026-08-12T00:00:00+08:00',
        'review_at' => '2026-08-20T00:00:00+08:00',
        'expires_at' => '2026-08-31T23:59:59+08:00',
        'approval' => [
            'key' => 'APR-EXC-0001',
            'outcome' => 'approved',
            'approver' => 'Managing Partner',
            'authority_basis' => 'Illustrative delegated exception authority',
            'decided_at' => '2026-08-10T09:45:00+08:00',
            'evidence_record_key' => 'EVD-EXC-0001',
        ],
    ];

    $resolved = resolvePolicyRegistry($registry, policyApprovalDecisionRecords());

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['exceptions'][0]['temporal_state'])->toBe('within_term')
        ->and($resolved['reports']['lifecycle_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);

    $registry['exceptions'][0]['approval'] = null;
    $registry['exceptions'][0]['expires_at'] = '2026-08-16T23:59:59+08:00';
    $invalid = resolvePolicyRegistry($registry, policyApprovalDecisionRecords());

    expect(array_column($invalid['reports']['lifecycle_gaps'], 'code'))
        ->toContain('missing_exception_approval')
        ->and(array_column($invalid['reports']['conflicts'], 'code'))
        ->toContain('active_exception_expired');
});
