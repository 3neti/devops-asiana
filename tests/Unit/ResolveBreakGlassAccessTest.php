<?php

use App\BreakGlassAccess\BreakGlassAccessDefinition;
use App\BreakGlassAccess\ResolveBreakGlassAccess;
use App\Engagements\ResolvedEngagements;
use App\Incidents\ResolvedIncidents;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;

function breakGlassDefinitionArray(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../resources/institution/break-glass-access.json'), true, flags: JSON_THROW_ON_ERROR);
}

function breakGlassEvidence(string $key): array
{
    return [
        'key' => $key,
        'record_type' => 'Break-glass Evidence',
        'subject' => 'Hypothetical emergency access',
        'actor' => 'Hypothetical authorized actor',
        'recorded_at' => '2026-08-18T10:30:00+08:00',
        'source' => 'Hypothetical institutional record',
        'reason' => 'Supports an emergency-access fact or decision',
        'state' => 'final',
    ];
}

function breakGlassPolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(file_get_contents(__DIR__.'/../../resources/institution/policies.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($effective) {
        foreach ($registry['policies'] as &$policy) {
            if (! in_array($policy['key'], ['production-access', 'authority-and-delegation', 'information-security', 'incident-management'], true)) {
                continue;
            }
            $policy['versions'][0]['status'] = 'effective';
            $policy['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
            $policy['versions'][0]['approval'] = [
                'key' => 'APR-'.strtoupper($policy['key']),
                'outcome' => 'approved',
                'approver' => 'Hypothetical Policy Authority',
                'authority_basis' => 'Hypothetical delegated authority',
                'decided_at' => '2026-07-30T09:00:00+08:00',
                'evidence_record_key' => 'EVD-BGA-POLICY',
            ];
        }
        unset($policy);
        $registry['evidence_records'][] = breakGlassEvidence('EVD-BGA-POLICY');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function breakGlassEngagements(): ResolvedEngagements
{
    return new ResolvedEngagements(
        schemaVersion: 1,
        governingPolicies: [],
        openingRequirements: [],
        engagements: [[
            'key' => 'ENG-HYPOTHETICAL-0001',
            'title' => 'Hypothetical Managed Cloud Operations',
            'client_key' => 'hypothetical-rural-bank',
            'client_name' => 'Hypothetical Rural Bank, Inc.',
            'may_perform_client_work' => true,
            'client_mandate' => [
                'systems' => ['Hypothetical Cloud Account'],
                'environments' => ['Production'],
                'permitted_actions' => ['Emergency service restoration'],
            ],
        ]],
        evidenceRecords: [],
        lifecycleCounts: [],
        conflicts: [],
        decisionGaps: [],
        evidenceGaps: [],
        readinessGaps: [],
    );
}

function breakGlassIncidents(): ResolvedIncidents
{
    return new ResolvedIncidents(
        schemaVersion: 1,
        governingPolicies: [],
        recordRequirements: [],
        incidentRecords: [[
            'key' => 'INC-HYPOTHETICAL-0001',
            'title' => 'Hypothetical Major Incident',
            'lifecycle_status' => 'active',
            'engagement_key' => 'ENG-HYPOTHETICAL-0001',
            'active_response' => true,
        ]],
        evidenceRecords: [],
        lifecycleCounts: [],
        conflicts: [],
        decisionGaps: [],
        evidenceGaps: [],
        readinessGaps: [],
    );
}

function activeBreakGlassRecord(string $status = 'activated'): array
{
    return [
        'key' => 'BGA-HYPOTHETICAL-0001',
        'title' => 'Hypothetical emergency production access',
        'lifecycle_status' => $status,
        'engagement_key' => 'ENG-HYPOTHETICAL-0001',
        'incident_key' => 'INC-HYPOTHETICAL-0001',
        'emergency' => [
            'condition' => 'Production administration plane unavailable through ordinary identity path.',
            'material_harm_if_delayed' => 'Client payment interruption will continue.',
            'why_ordinary_process_is_insufficient' => 'Ordinary privileged account cannot authenticate during the outage.',
            'requested_by' => 'Hypothetical Incident Commander',
            'requested_at' => '2026-08-18T09:00:00+08:00',
        ],
        'actor' => [
            'key' => 'hypothetical-engineer',
            'name' => 'Hypothetical Engineer',
            'account_identifier' => 'bg-hypothetical-engineer',
            'account_type' => 'named',
        ],
        'scope' => [
            'client_key' => 'hypothetical-rural-bank',
            'system' => 'Hypothetical Cloud Account',
            'environment' => 'Production',
            'permissions' => ['Restart failed identity control plane'],
            'purpose' => 'Restore the ordinary identity and access path.',
            'client_mandate_action' => 'Emergency service restoration',
            'permitted_actions' => ['Inspect identity service', 'Restart failed identity service'],
            'prohibited_actions' => ['Export Client data', 'Alter unrelated workloads'],
        ],
        'risk' => [
            'classification' => 'critical',
            'impact' => 'Privileged production control-plane access',
            'data_sensitivity' => 'Client confidential infrastructure metadata',
            'risk_owner' => 'Angelica Anaïs C. Santos',
            'controls' => ['Independent approval', 'Session monitoring', 'Automatic expiry'],
            'residual_risk' => 'Bounded emergency privilege remains material.',
        ],
        'identity_controls' => [
            'mfa' => true,
            'credential_owner' => 'Hypothetical Rural Bank, Inc.',
            'custody' => 'Client-owned emergency credential under dual control',
            'vault_reference' => 'Client vault record BG-HYP-01',
            'session_attribution' => 'Named account and session recording',
            'rotation_required' => true,
            'secret_material_present' => false,
        ],
        'approvals' => [
            breakGlassApproval('client_emergency_authority', 'client-authority'),
            breakGlassApproval('firm_emergency_authority', 'firm-authority'),
            breakGlassApproval('independent_security_authority', 'security-authority'),
        ],
        'window' => [
            'activates_at' => '2026-08-18T09:15:00+08:00',
            'expires_at' => '2026-08-18T10:15:00+08:00',
            'automatic_expiry' => true,
            'renewal_permitted' => false,
        ],
        'activation' => [
            'activated_by' => 'Hypothetical Access Custodian',
            'authority_basis' => 'Approved Break-glass Access Record',
            'account_identifier' => 'bg-hypothetical-engineer',
            'verification' => 'verified',
            'activated_at' => '2026-08-18T09:15:00+08:00',
            'evidence_record_key' => 'EVD-BGA-ACTIVATION',
        ],
        'activity_log' => [[
            'key' => 'BGA-ACT-001',
            'occurred_at' => '2026-08-18T09:20:00+08:00',
            'actor_key' => 'hypothetical-engineer',
            'action' => 'Inspected identity service health.',
            'target' => 'Production identity service',
            'result' => 'Failed service instance identified.',
            'source' => 'Recorded emergency session',
            'evidence_record_key' => 'EVD-BGA-ACTIVITY',
        ]],
        'monitoring' => [
            'monitored_by_key' => 'hypothetical-security-observer',
            'monitored_by' => 'Hypothetical Security Observer',
            'mechanism' => 'Live session observation and immutable command logging',
            'scope_violation_response' => 'Immediately terminate the session and notify Incident Commander.',
            'result' => 'No scope violation observed.',
            'evidence_record_key' => 'EVD-BGA-MONITORING',
        ],
    ];
}

function breakGlassApproval(string $type, string $approverKey): array
{
    return [
        'approval_type' => $type,
        'outcome' => 'approved',
        'approver_key' => $approverKey,
        'approver' => 'Hypothetical '.str_replace('_', ' ', $type),
        'authority_basis' => 'Emergency authority matrix',
        'decided_at' => '2026-08-18T09:10:00+08:00',
        'evidence_record_key' => 'EVD-BGA-APPROVAL',
    ];
}

function reviewedBreakGlassRecord(string $status = 'under_review'): array
{
    $record = activeBreakGlassRecord($status);
    $record['termination'] = [
        'reason' => 'Emergency work completed and absolute window ended.',
        'terminated_by' => 'Hypothetical Access Custodian',
        'terminated_at' => '2026-08-18T10:15:00+08:00',
        'permissions_removed' => true,
        'verified_by' => 'Hypothetical Independent Verifier',
        'verification_result' => 'verified',
        'credential_rotated' => true,
        'evidence_record_key' => 'EVD-BGA-TERMINATION',
    ];
    $record['disclosure'] = [
        'communicated_by' => 'Hypothetical Communication Owner',
        'client_audience' => 'Client Incident Authority',
        'responsible_partner' => 'Angelica Anaïs C. Santos',
        'summary' => 'Emergency access purpose, activity, result, and termination disclosed.',
        'communicated_at' => '2026-08-18T10:20:00+08:00',
        'evidence_record_key' => 'EVD-BGA-DISCLOSURE',
    ];
    $record['retrospective_review'] = [
        'reviewed_by_key' => 'hypothetical-reviewer',
        'reviewed_by' => 'Hypothetical Independent Reviewer',
        'reviewed_at' => '2026-08-18T11:00:00+08:00',
        'blameless' => true,
        'necessity' => 'Emergency route was necessary to restore ordinary identity.',
        'authority' => 'All required approvals preceded activation.',
        'scope_adherence' => 'Observed activity remained within scope.',
        'activity_assessment' => 'Activity log reconciled to session recording.',
        'outcome' => 'Ordinary access path restored.',
        'credential_handling' => 'Emergency credential rotated after use.',
        'control_performance' => 'Expiry and monitoring controls operated as designed.',
        'evidence_record_key' => 'EVD-BGA-REVIEW',
    ];
    $record['corrective_actions'] = [[
        'key' => 'CA-BGA-001',
        'description' => 'Improve ordinary identity-path resilience.',
        'owner' => 'Hypothetical Technical Lead',
        'due_at' => '2026-09-01T17:00:00+08:00',
        'status' => 'open',
    ]];

    return $record;
}

function resolveBreakGlassRecord(array $record, string $asOf = '2026-08-18T09:30:00+08:00'): array
{
    $definition = breakGlassDefinitionArray();
    $definition['access_records'] = [$record];
    foreach (['APPROVAL', 'ACTIVATION', 'ACTIVITY', 'MONITORING', 'TERMINATION', 'DISCLOSURE', 'REVIEW', 'CLOSURE'] as $suffix) {
        $definition['evidence_records'][] = breakGlassEvidence("EVD-BGA-{$suffix}");
    }

    return (new ResolveBreakGlassAccess)->handle(
        BreakGlassAccessDefinition::fromArray($definition),
        breakGlassEngagements(),
        breakGlassIncidents(),
        breakGlassPolicies(),
        new DateTimeImmutable($asOf),
    )->toArray();
}

test('canonical Break-glass registry remains honest about activation readiness', function () {
    $resolved = (new ResolveBreakGlassAccess)->handle(
        BreakGlassAccessDefinition::fromArray(breakGlassDefinitionArray()),
        breakGlassEngagements(),
        breakGlassIncidents(),
        breakGlassPolicies(false),
        new DateTimeImmutable('2026-08-18T09:30:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['access_records'])->toBe(0)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(4);
});

test('complete emergency authority is usable only inside its approved window', function () {
    $resolved = resolveBreakGlassRecord(activeBreakGlassRecord());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['access_records'][0]['may_use_break_glass'])->toBeTrue()
        ->and($resolved['access_records'][0]['operational_status'])->toBe('active_emergency_authority');
});

test('credential possession and activation cannot replace explicit approvals', function () {
    $record = activeBreakGlassRecord();
    unset($record['approvals']);

    $resolved = resolveBreakGlassRecord($record);

    expect($resolved['access_records'][0]['may_use_break_glass'])->toBeFalse()
        ->and($resolved['reports']['decision_gaps'])->toContainEqual([
            'code' => 'missing_break_glass_approvals',
            'message' => 'Hypothetical emergency production access lacks emergency approvals.',
        ]);
});

test('an actor cannot independently approve their own emergency access', function () {
    $record = activeBreakGlassRecord();
    $record['approvals'][2]['approver_key'] = 'hypothetical-engineer';

    $resolved = resolveBreakGlassRecord($record);

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['access_records'][0]['may_use_break_glass'])->toBeFalse();
});

test('absolute expiry ends authority even before technical removal is verified', function () {
    $resolved = resolveBreakGlassRecord(activeBreakGlassRecord(), '2026-08-18T10:15:00+08:00');

    expect($resolved['access_records'][0]['window_state'])->toBe('expired')
        ->and($resolved['access_records'][0]['may_use_break_glass'])->toBeFalse()
        ->and($resolved['access_records'][0]['operational_status'])->toBe('expired_authority');
});

test('emergency authority cannot be silently extended in place', function () {
    $record = activeBreakGlassRecord();
    $record['extended_until'] = '2026-08-18T11:15:00+08:00';

    $resolved = resolveBreakGlassRecord($record);

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['access_records'][0]['may_use_break_glass'])->toBeFalse();
});

test('reviewed emergency use becomes ready for separately authorized closure', function () {
    $resolved = resolveBreakGlassRecord(reviewedBreakGlassRecord(), '2026-08-18T11:30:00+08:00');

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['access_records'][0]['may_use_break_glass'])->toBeFalse()
        ->and($resolved['access_records'][0]['operational_status'])->toBe('ready_for_closure');
});

test('closure requires a separate evidenced decision after review', function () {
    $record = reviewedBreakGlassRecord('closed');
    $record['closure'] = [
        'closed_by' => 'Hypothetical Responsible Partner',
        'authority_basis' => 'Production Access Policy',
        'closed_at' => '2026-08-18T11:30:00+08:00',
        'evidence_record_key' => 'EVD-BGA-CLOSURE',
    ];

    $resolved = resolveBreakGlassRecord($record, '2026-08-18T11:30:00+08:00');

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['access_records'][0]['operational_status'])->toBe('closed_verified');
});
