<?php

use App\Changes\ChangeDefinition;
use App\Changes\ResolveChanges;
use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;
use App\ProductionAccess\ResolvedProductionAccess;

function changeDefinitionArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/changes.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function changeEvidence(string $key, string $type = 'Change Evidence'): array
{
    return [
        'key' => $key,
        'record_type' => $type,
        'subject' => 'Hypothetical production Change',
        'actor' => 'Hypothetical authorized actor',
        'recorded_at' => '2026-08-18T11:00:00+08:00',
        'source' => 'Hypothetical institutional record',
        'reason' => 'Supports a Change control decision or action',
        'state' => 'final',
    ];
}

function changePolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if ($effective) {
        foreach ($registry['policies'] as &$policy) {
            if (! in_array($policy['key'], ['change-management', 'authority-and-delegation', 'production-access'], true)) {
                continue;
            }

            $policy['versions'][0]['status'] = 'effective';
            $policy['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
            $policy['versions'][0]['approval'] = [
                'key' => 'APR-'.strtoupper($policy['key']),
                'outcome' => 'approved',
                'approver' => 'Hypothetical policy authority',
                'authority_basis' => 'Hypothetical delegated authority',
                'decided_at' => '2026-07-30T09:00:00+08:00',
                'evidence_record_key' => 'EVD-HYPOTHETICAL-POLICY',
            ];
        }
        unset($policy);

        $registry['evidence_records'][] = changeEvidence('EVD-HYPOTHETICAL-POLICY', 'Policy Approval');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function changeEngagements(bool $open = true): ResolvedEngagements
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
            'operational_status' => $open ? 'open_engagement' : 'approved_not_open',
            'may_perform_client_work' => $open,
            'client_mandate' => [
                'systems' => ['Hypothetical Cloud Account'],
                'environments' => ['Production'],
                'permitted_actions' => ['Operate approved infrastructure'],
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

function changeProductionAccess(bool $active = true): ResolvedProductionAccess
{
    return new ResolvedProductionAccess(
        schemaVersion: 1,
        governingPolicies: [],
        grantRequirements: [],
        accessGrants: [[
            'key' => 'PAG-HYPOTHETICAL-0001',
            'title' => 'Hypothetical Production Access Grant',
            'may_use_access' => $active,
            'operational_status' => $active ? 'active_authority' : 'blocked_active_grant',
            'engagement_key' => 'ENG-HYPOTHETICAL-0001',
            'actor' => ['key' => 'hypothetical-engineer', 'name' => 'Hypothetical Engineer'],
            'scope' => [
                'client_key' => 'hypothetical-rural-bank',
                'system' => 'Hypothetical Cloud Account',
                'environment' => 'Production',
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

function completeScheduledChange(string $type = 'normal'): array
{
    $approvals = match ($type) {
        'standard' => [[
            'approval_type' => 'standard_change_authority',
            'outcome' => 'approved',
            'approver' => 'Hypothetical Standard Change Authority',
            'authority_basis' => 'Current approved Standard Change Definition',
            'decided_at' => '2026-08-14T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CHANGE-APPROVAL',
        ]],
        'emergency' => [[
            'approval_type' => 'emergency_change_authority',
            'outcome' => 'approved',
            'approver' => 'Hypothetical Emergency Change Authority',
            'authority_basis' => 'Expedited authority under Change Management Policy',
            'decided_at' => '2026-08-14T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CHANGE-APPROVAL',
        ]],
        default => [
            [
                'approval_type' => 'client_change_authority',
                'outcome' => 'approved',
                'approver' => 'Hypothetical Client Change Authority',
                'authority_basis' => 'Client Mandate and Change request',
                'decided_at' => '2026-08-14T09:00:00+08:00',
                'evidence_record_key' => 'EVD-CHANGE-CLIENT-APPROVAL',
            ],
            [
                'approval_type' => 'firm_change_authority',
                'outcome' => 'approved',
                'approver' => 'Hypothetical Firm Change Authority',
                'authority_basis' => 'Change Management Policy and Authority Matrix',
                'decided_at' => '2026-08-14T10:00:00+08:00',
                'evidence_record_key' => 'EVD-CHANGE-FIRM-APPROVAL',
            ],
        ],
    };

    $classification = [
        'classified_as' => $type,
        'classified_by' => 'Hypothetical Change Manager',
        'authority_basis' => 'Change Management Policy',
        'reason' => 'Classification follows the applicable approval and review path.',
        'evidence_record_key' => 'EVD-CHANGE-CLASSIFICATION',
    ];

    if ($type === 'standard') {
        $classification['standard_definition'] = [
            'key' => 'STD-HYPOTHETICAL-DEPLOY',
            'version' => '1.0',
            'current' => true,
            'eligibility_confirmed' => true,
            'review_at' => '2026-12-01T00:00:00+08:00',
        ];
    }

    if ($type === 'emergency') {
        $classification['emergency_reason'] = 'Immediate remediation prevents continuing material harm.';
        $classification['delay_increases_material_harm'] = true;
    }

    return [
        'key' => 'CHG-HYPOTHETICAL-0001',
        'title' => 'Hypothetical production release',
        'lifecycle_status' => 'scheduled',
        'change_type' => $type,
        'engagement_key' => 'ENG-HYPOTHETICAL-0001',
        'request' => [
            'requested_by' => 'Hypothetical Client Requestor',
            'requested_at' => '2026-08-13T09:00:00+08:00',
            'rationale' => 'Deploy an approved corrective release.',
            'desired_outcome' => 'Correct the identified production behavior.',
            'source_reference' => 'Client request HYP-42',
            'evidence_record_key' => 'EVD-CHANGE-REQUEST',
        ],
        'scope' => [
            'client_key' => 'hypothetical-rural-bank',
            'system' => 'Hypothetical Cloud Account',
            'environment' => 'Production',
            'service' => 'Hypothetical Payment Service',
            'components' => ['payment-api'],
            'implementation_plan' => ['Deploy immutable artifact release-v2.4.1', 'Observe service health'],
            'expected_outcome' => 'Release v2.4.1 operates without regression.',
            'client_mandate_action' => 'Operate approved infrastructure',
            'excluded_actions' => ['Database destructive operations'],
        ],
        'classification' => $classification,
        'risk' => [
            'classification' => 'moderate',
            'likelihood' => 'Unlikely',
            'impact' => 'Temporary service degradation',
            'affected_services' => ['Hypothetical Payment Service'],
            'client_impact' => 'Potential brief request latency',
            'risk_owner' => 'angelica-santos',
            'controls' => ['Canary deployment', 'Active monitoring', 'Rollback threshold'],
            'residual_risk' => 'Low after controls',
        ],
        'technical_review' => [
            'reviewed_by' => 'Hypothetical Technical Reviewer',
            'competence_basis' => 'Service and deployment expertise',
            'implementation_reviewed' => true,
            'dependencies_reviewed' => true,
            'tests_reviewed' => true,
            'monitoring_reviewed' => true,
            'recovery_reviewed' => true,
            'outcome' => 'recommended',
            'reviewed_at' => '2026-08-14T08:00:00+08:00',
            'evidence_record_key' => 'EVD-CHANGE-TECHNICAL-REVIEW',
        ],
        'recovery' => [
            'viable' => true,
            'strategy' => 'Redeploy the prior immutable artifact.',
            'steps' => ['Stop rollout', 'Deploy release-v2.4.0', 'Verify service health'],
            'triggers' => ['Error rate exceeds threshold', 'Health checks fail'],
            'owner' => 'Hypothetical Technical Lead',
            'estimated_recovery_time' => '15 minutes',
            'irreversible' => false,
            'evidence_record_key' => 'EVD-CHANGE-RECOVERY',
            'backup_confirmation' => [
                'required' => true,
                'confirmed' => true,
                'recovery_point' => 'Immutable release-v2.4.0 and database snapshot HYP-SNAP-42',
                'confirmed_by' => 'Hypothetical Independent Verifier',
                'confirmed_at' => '2026-08-14T08:30:00+08:00',
                'evidence_record_key' => 'EVD-CHANGE-BACKUP',
            ],
        ],
        'policy_exception_keys' => [],
        'approvals' => $approvals,
        'schedule' => [
            'starts_at' => '2026-08-18T10:00:00+08:00',
            'ends_at' => '2026-08-18T14:00:00+08:00',
            'timezone' => 'Asia/Manila',
            'communication_plan' => ['Notify Client before and after execution'],
            'monitoring_plan' => ['Watch error rate, latency, and health checks'],
            'abort_conditions' => ['Error threshold breached', 'Unexpected data impact'],
        ],
        'executor' => [
            'key' => 'hypothetical-engineer',
            'name' => 'Hypothetical Engineer',
        ],
        'access_grant_key' => 'PAG-HYPOTHETICAL-0001',
    ];
}

function closedChange(): array
{
    $change = completeScheduledChange();
    $change['lifecycle_status'] = 'closed';
    $change['execution'] = [
        'executed_by' => 'hypothetical-engineer',
        'authority_basis' => 'Approved Change CHG-HYPOTHETICAL-0001',
        'artifact_identifier' => 'release-v2.4.1',
        'target' => 'Hypothetical Payment Service / Production',
        'deployment_output_reference' => 'Deployment run HYP-RUN-42',
        'result' => 'succeeded',
        'started_at' => '2026-08-18T10:30:00+08:00',
        'completed_at' => '2026-08-18T10:45:00+08:00',
        'evidence_record_key' => 'EVD-CHANGE-EXECUTION',
    ];
    $change['verification'] = [
        'verified_by' => 'Hypothetical Independent Verifier',
        'expected_outcomes' => ['Release v2.4.1 healthy'],
        'observed_outcomes' => ['Health checks and telemetry healthy'],
        'result' => 'verified',
        'independent_from_executor' => true,
        'verified_at' => '2026-08-18T10:50:00+08:00',
        'evidence_record_key' => 'EVD-CHANGE-VERIFICATION',
    ];
    $change['outcome'] = [
        'result' => 'succeeded',
        'summary' => 'Approved release deployed and verified.',
        'client_impact' => false,
        'follow_up_actions' => ['Continue ordinary service monitoring'],
        'evidence_record_key' => 'EVD-CHANGE-OUTCOME',
    ];
    $change['communication'] = [
        'owner' => 'Hypothetical Communication Owner',
        'audiences' => ['Client operations contact', 'Responsible Partner'],
        'status' => 'Completion communicated',
        'communicated_at' => '2026-08-18T10:55:00+08:00',
        'evidence_record_key' => 'EVD-CHANGE-COMMUNICATION',
    ];
    $change['closure'] = [
        'closed_by' => 'Hypothetical Change Authority',
        'authority_basis' => 'Change Management Policy',
        'verification_complete' => true,
        'evidence_complete' => true,
        'closed_at' => '2026-08-18T11:00:00+08:00',
        'evidence_record_key' => 'EVD-CHANGE-CLOSURE',
    ];

    return $change;
}

function changeDefinitionWithRecord(array $change): ChangeDefinition
{
    $definition = changeDefinitionArray();
    $definition['change_records'] = [$change];

    foreach ([
        'EVD-CHANGE-REQUEST',
        'EVD-CHANGE-CLASSIFICATION',
        'EVD-CHANGE-TECHNICAL-REVIEW',
        'EVD-CHANGE-RECOVERY',
        'EVD-CHANGE-BACKUP',
        'EVD-CHANGE-APPROVAL',
        'EVD-CHANGE-CLIENT-APPROVAL',
        'EVD-CHANGE-FIRM-APPROVAL',
        'EVD-CHANGE-EXECUTION',
        'EVD-CHANGE-VERIFICATION',
        'EVD-CHANGE-OUTCOME',
        'EVD-CHANGE-COMMUNICATION',
        'EVD-CHANGE-CLOSURE',
        'EVD-CHANGE-REVIEW',
    ] as $key) {
        $definition['evidence_records'][] = changeEvidence($key);
    }

    return ChangeDefinition::fromArray($definition);
}

function resolveChangeRecord(array $change, bool $openEngagement = true, bool $activeAccess = true): array
{
    return (new ResolveChanges)->handle(
        changeDefinitionWithRecord($change),
        changeEngagements($openEngagement),
        changeProductionAccess($activeAccess),
        changePolicies(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('canonical Change Management remains honest about institutional readiness', function () {
    $resolved = (new ResolveChanges)->handle(
        ChangeDefinition::fromArray(changeDefinitionArray()),
        changeEngagements(),
        changeProductionAccess(),
        changePolicies(false),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['change_records'])->toBe(0)
        ->and($resolved['counts']['executable_authority'])->toBe(0)
        ->and($resolved['record_requirements'])->toHaveCount(15)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(3);
});

test('a complete scheduled Change creates bounded execution authority', function () {
    $resolved = resolveChangeRecord(completeScheduledChange());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['change_records'][0]['may_execute_change'])->toBeTrue()
        ->and($resolved['change_records'][0]['operational_status'])->toBe('authorized_for_execution')
        ->and($resolved['counts']['executable_authority'])->toBe(1);
});

test('an Active Access Grant does not authorize a Change without specific approvals', function () {
    $change = completeScheduledChange();
    $change['approvals'] = [];

    $resolved = resolveChangeRecord($change);

    expect($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('required_change_approvals_not_satisfied');
});

test('a production Change cannot execute without a viable evidenced recovery plan', function () {
    $change = completeScheduledChange();
    $change['recovery']['viable'] = false;
    $change['recovery']['backup_confirmation']['confirmed'] = false;

    $resolved = resolveChangeRecord($change);

    expect($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('production_change_without_recovery');
});

test('execution cannot infer approval or occur before approval and its window', function () {
    $change = closedChange();
    $change['approvals'] = [];
    $change['execution']['started_at'] = '2026-08-14T07:00:00+08:00';

    $resolved = resolveChangeRecord($change);
    $conflictCodes = array_column($resolved['reports']['conflicts'], 'code');

    expect($conflictCodes)->toContain('change_executed_before_approval')
        ->and($conflictCodes)->toContain('change_executed_outside_window');
});

test('executor identity requires a matching current Active Access Grant', function () {
    $resolved = resolveChangeRecord(completeScheduledChange(), activeAccess: false);

    expect($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('change_executor_without_active_access');
});

test('Standard Change classification requires a current eligible definition', function () {
    $change = completeScheduledChange('standard');
    $change['classification']['standard_definition']['eligibility_confirmed'] = false;

    $resolved = resolveChangeRecord($change);

    expect($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('invalid_change_classification');
});

test('Emergency Change classification requires material-harm justification', function () {
    $change = completeScheduledChange('emergency');
    $change['classification']['delay_increases_material_harm'] = false;

    $resolved = resolveChangeRecord($change);

    expect($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('invalid_change_classification');
});

test('verification remains separate from execution and follows completion', function () {
    $change = closedChange();
    $change['verification']['verified_at'] = '2026-08-18T10:40:00+08:00';

    $resolved = resolveChangeRecord($change);

    expect(array_column($resolved['reports']['conflicts'], 'code'))->toContain('change_verified_before_execution_completed');
});

test('successful closure preserves execution verification communication and evidence', function () {
    $resolved = resolveChangeRecord(closedChange());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['change_records'][0]['may_execute_change'])->toBeFalse()
        ->and($resolved['change_records'][0]['operational_status'])->toBe('closed_verified');
});

test('closure is not recognized without explicit closure authority and evidence', function () {
    $change = closedChange();
    unset($change['closure']);

    $resolved = resolveChangeRecord($change);

    expect($resolved['change_records'][0]['operational_status'])->not->toBe('closed_verified')
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('incomplete_change_closure')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))->toContain('missing_change_closure_evidence');
});
