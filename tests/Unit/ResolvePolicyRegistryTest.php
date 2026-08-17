<?php

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

function resolvePolicyRegistry(array $registry): array
{
    return (new ResolvePolicyRegistry)
        ->handle(
            PolicyRegistryDefinition::fromArray($registry),
            new DateTimeImmutable('2026-08-17T12:00:00+08:00'),
        )
        ->toArray();
}

function effectivePolicyRegistry(): array
{
    $registry = policyRegistryArray();
    $registry['evidence_records'][] = [
        'key' => 'EVD-POL-0001',
        'record_type' => 'Policy Approval',
        'subject' => 'Partnership Governance Policy version 0.1',
        'actor' => 'Founding Partners',
        'recorded_at' => '2026-08-01T10:00:00+08:00',
        'source' => 'Governance Decision Record GDR-0001',
        'reason' => 'Evidence of explicit policy approval',
        'state' => 'final',
    ];
    $registry['policies'][0]['versions'][0]['status'] = 'effective';
    $registry['policies'][0]['versions'][0]['effective_at'] = '2026-08-15T00:00:00+08:00';
    $registry['policies'][0]['versions'][0]['approval'] = [
        'key' => 'APR-POL-0001',
        'outcome' => 'approved',
        'approver' => 'Founding Partners',
        'authority_basis' => 'Partnership Agreement and recorded Partner decision',
        'decided_at' => '2026-08-01T09:45:00+08:00',
        'evidence_record_key' => 'EVD-POL-0001',
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

test('it accepts an effective policy only with separate approval and evidence', function () {
    $resolved = resolvePolicyRegistry(effectivePolicyRegistry());

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['counts']['by_status']['effective'])->toBe(1)
        ->and($resolved['policies'][0]['current']['content_integrity'])->toBe('verified')
        ->and($resolved['reports']['lifecycle_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);
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

    $resolved = resolvePolicyRegistry($registry);

    expect($resolved)
        ->compiler_status->toBe('consistent')
        ->and($resolved['exceptions'][0]['temporal_state'])->toBe('within_term')
        ->and($resolved['reports']['lifecycle_gaps'])->toBe([])
        ->and($resolved['reports']['evidence_gaps'])->toBe([]);

    $registry['exceptions'][0]['approval'] = null;
    $registry['exceptions'][0]['expires_at'] = '2026-08-16T23:59:59+08:00';
    $invalid = resolvePolicyRegistry($registry);

    expect(array_column($invalid['reports']['lifecycle_gaps'], 'code'))
        ->toContain('missing_exception_approval')
        ->and(array_column($invalid['reports']['conflicts'], 'code'))
        ->toContain('active_exception_expired');
});
