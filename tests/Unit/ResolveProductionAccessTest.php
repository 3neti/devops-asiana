<?php

use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;
use App\ProductionAccess\ProductionAccessDefinition;
use App\ProductionAccess\ResolveProductionAccess;

function productionAccessDefinitionArray(): array
{
    return json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/production-access.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

function productionAccessPolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(
        file_get_contents(__DIR__.'/../../resources/institution/policies.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    if ($effective) {
        foreach ($registry['policies'] as &$policy) {
            if (! in_array($policy['key'], ['production-access', 'authority-and-delegation', 'information-security'], true)) {
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

        $registry['evidence_records'][] = productionAccessEvidence('EVD-HYPOTHETICAL-POLICY', 'Policy Approval');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function productionAccessEngagements(bool $open = true): ResolvedEngagements
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

function productionAccessEvidence(string $key, string $type = 'Production Access Evidence'): array
{
    return [
        'key' => $key,
        'record_type' => $type,
        'subject' => 'Hypothetical named Production Access Grant',
        'actor' => 'Hypothetical authorized actor',
        'recorded_at' => '2026-08-16T10:00:00+08:00',
        'source' => 'Hypothetical institutional record',
        'reason' => 'Supports a Production Access control decision',
        'state' => 'final',
    ];
}

function completeProductionAccessGrant(string $status = 'active', string $type = 'standard'): array
{
    $approvals = [
        [
            'approval_type' => 'client_system_owner',
            'outcome' => 'approved',
            'approver' => 'Hypothetical Client System Owner',
            'authority_basis' => 'Client Mandate under ENG-HYPOTHETICAL-0001',
            'decided_at' => '2026-08-14T09:00:00+08:00',
            'evidence_record_key' => 'EVD-ACCESS-CLIENT-APPROVAL',
        ],
        [
            'approval_type' => 'firm_access_authority',
            'outcome' => 'approved',
            'approver' => 'Hypothetical Firm Access Authority',
            'authority_basis' => 'Production Access Policy and Authority Matrix',
            'decided_at' => '2026-08-14T10:00:00+08:00',
            'evidence_record_key' => 'EVD-ACCESS-FIRM-APPROVAL',
        ],
    ];

    if ($type === 'privileged') {
        $approvals[] = [
            'approval_type' => 'independent_privileged_authority',
            'outcome' => 'approved',
            'approver' => 'Hypothetical Independent Privileged Authority',
            'authority_basis' => 'Independent approval required by Production Access Policy',
            'decided_at' => '2026-08-14T11:00:00+08:00',
            'evidence_record_key' => 'EVD-ACCESS-PRIVILEGED-APPROVAL',
        ];
    }

    return [
        'key' => 'PAG-HYPOTHETICAL-0001',
        'title' => 'Hypothetical named Production Access Grant',
        'lifecycle_status' => $status,
        'grant_type' => $type,
        'request_evidence_record_key' => 'EVD-ACCESS-REQUEST',
        'actor' => [
            'key' => 'hypothetical-engineer',
            'name' => 'Hypothetical Engineer',
            'actor_type' => 'person',
            'account_type' => 'named',
            'firm_relationship' => 'Associate',
        ],
        'engagement_key' => 'ENG-HYPOTHETICAL-0001',
        'scope' => [
            'client_key' => 'hypothetical-rural-bank',
            'system' => 'Hypothetical Cloud Account',
            'environment' => 'Production',
            'account_identifier' => 'engineer@example.test',
            'permission_set' => ['deploy-approved-release', 'read-operational-logs'],
            'purpose' => 'Operate approved Client infrastructure.',
            'least_privilege_justification' => 'Only deployment and diagnostic read permissions are required.',
            'client_mandate_action' => 'Operate approved infrastructure',
            'engagement_access_basis' => 'Named-user access permitted by the Engagement operating terms.',
            'prohibited_actions' => ['Change Client billing relationships'],
        ],
        'risk' => [
            'classification' => $type === 'privileged' ? 'High' : 'Moderate',
            'risk_owner' => 'angelica-santos',
            'privileged' => $type === 'privileged',
            'high_risk_actions' => $type === 'privileged' ? ['Assume administrative role'] : [],
            'high_risk_actions_require_specific_approval' => $type === 'privileged',
        ],
        'prerequisites' => [
            'identity_verified' => true,
            'mfa_required' => true,
            'mfa_verified' => true,
            'device_compliant' => true,
            'training_current' => true,
        ],
        'credential_handling' => [
            'asset_owner' => 'client',
            'custodian' => 'Client-approved secrets platform',
            'storage_reference' => 'Reference only: client vault path ACCESS/PAG-HYPOTHETICAL-0001',
            'rotation_owner' => 'Client System Owner',
            'rotation_trigger' => 'Expiry, compromise, or role change',
            'secret_material_present' => false,
        ],
        'logging' => [
            'activity_logging_required' => true,
            'log_owner' => 'Client',
            'retention_basis' => 'Engagement and Client retention requirements',
            'evidence_requirements' => ['Authentication', 'Privilege use', 'Material actions'],
        ],
        'validity' => [
            'starts_at' => '2026-08-15T09:00:00+08:00',
            'review_at' => '2026-09-15T09:00:00+08:00',
            'expires_at' => '2026-10-15T09:00:00+08:00',
        ],
        'lifecycle_control' => [
            'revocation_owner' => 'Firm Access Authority',
            'revocation_method' => 'Disable named account and revoke assigned roles',
            'expiry_enforced' => true,
        ],
        'approvals' => $approvals,
        'provisioning' => [
            'provisioned_by' => 'Hypothetical Client Administrator',
            'authority_basis' => 'Approved Access Grant',
            'mechanism' => 'Client identity provider and cloud IAM',
            'account_identifier' => 'engineer@example.test',
            'provisioned_at' => '2026-08-15T09:00:00+08:00',
            'evidence_record_key' => 'EVD-ACCESS-PROVISIONING',
        ],
        'verification' => [
            'verified_by' => 'Hypothetical Independent Verifier',
            'result' => 'verified',
            'verified_at' => '2026-08-15T10:00:00+08:00',
            'observed_permissions' => ['deploy-approved-release', 'read-operational-logs'],
            'evidence_record_key' => 'EVD-ACCESS-VERIFICATION',
        ],
    ];
}

function productionAccessDefinitionWithGrant(array $grant): ProductionAccessDefinition
{
    $definition = productionAccessDefinitionArray();
    $definition['access_grants'] = [$grant];

    foreach ([
        'EVD-ACCESS-REQUEST',
        'EVD-ACCESS-CLIENT-APPROVAL',
        'EVD-ACCESS-FIRM-APPROVAL',
        'EVD-ACCESS-PRIVILEGED-APPROVAL',
        'EVD-ACCESS-PROVISIONING',
        'EVD-ACCESS-VERIFICATION',
        'EVD-ACCESS-REVOCATION',
    ] as $evidenceKey) {
        $definition['evidence_records'][] = productionAccessEvidence($evidenceKey);
    }

    return ProductionAccessDefinition::fromArray($definition);
}

function resolveProductionAccessGrant(array $grant, bool $openEngagement = true): array
{
    return (new ResolveProductionAccess)->handle(
        productionAccessDefinitionWithGrant($grant),
        productionAccessEngagements($openEngagement),
        productionAccessPolicies(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('canonical Production Access remains honest about institutional readiness', function () {
    $resolved = (new ResolveProductionAccess)->handle(
        ProductionAccessDefinition::fromArray(productionAccessDefinitionArray()),
        productionAccessEngagements(),
        productionAccessPolicies(false),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['access_grants'])->toBe(0)
        ->and($resolved['counts']['active_authority'])->toBe(0)
        ->and($resolved['grant_requirements'])->toHaveCount(13)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(3);
});

test('a complete active named grant creates bounded access authority', function () {
    $resolved = resolveProductionAccessGrant(completeProductionAccessGrant());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['access_grants'][0]['may_use_access'])->toBeTrue()
        ->and($resolved['access_grants'][0]['operational_status'])->toBe('active_authority')
        ->and($resolved['counts']['active_authority'])->toBe(1);
});

test('technical access never substitutes for an open Engagement or Client Mandate', function () {
    $outsideMandate = completeProductionAccessGrant();
    $outsideMandate['scope']['client_mandate_action'] = 'Delete production database';

    $withoutEngagement = resolveProductionAccessGrant(completeProductionAccessGrant(), false);
    $outsideMandate = resolveProductionAccessGrant($outsideMandate);

    expect($withoutEngagement['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and(array_column($withoutEngagement['reports']['decision_gaps'], 'code'))->toContain('access_without_open_engagement')
        ->and($outsideMandate['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and(array_column($outsideMandate['reports']['decision_gaps'], 'code'))->toContain('access_outside_client_mandate');
});

test('approval and provisioning do not imply active authority', function (string $status, string $expected) {
    $grant = completeProductionAccessGrant($status);

    if ($status === 'approved') {
        unset($grant['provisioning'], $grant['verification']);
    }

    if ($status === 'provisioned') {
        unset($grant['verification']);
    }

    $resolved = resolveProductionAccessGrant($grant);

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and($resolved['access_grants'][0]['operational_status'])->toBe($expected);
})->with([
    ['approved', 'approved_not_provisioned'],
    ['provisioned', 'provisioned_not_active'],
]);

test('privileged access requires an independent authority and explicit high-risk boundary', function () {
    $grant = completeProductionAccessGrant('active', 'privileged');
    array_pop($grant['approvals']);
    $grant['risk']['high_risk_actions_require_specific_approval'] = false;

    $resolved = resolveProductionAccessGrant($grant);
    $codes = array_column($resolved['reports']['decision_gaps'], 'code');

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and($codes)->toContain('incomplete_access_risk')
        ->and($codes)->toContain('required_access_approvals_not_satisfied');
});

test('expired or overdue access cannot remain active', function (string $field, string $value, string $code) {
    $grant = completeProductionAccessGrant();
    $grant['validity'][$field] = $value;

    $resolved = resolveProductionAccessGrant($grant);

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain($code);
})->with([
    ['expires_at', '2026-08-17T09:00:00+08:00', 'active_access_outside_validity'],
    ['review_at', '2026-08-17T09:00:00+08:00', 'active_access_review_overdue'],
]);

test('credential secrets are prohibited from canonical Access Grant records', function () {
    $grant = completeProductionAccessGrant();
    $grant['credential_handling']['password'] = 'must-not-be-recorded';

    $resolved = resolveProductionAccessGrant($grant);

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain('credential_secret_in_repository');
});

test('provisioning and verification remain ordered and independently evidenced', function () {
    $grant = completeProductionAccessGrant();
    $grant['provisioning']['provisioned_at'] = '2026-08-14T08:00:00+08:00';
    $grant['verification']['observed_permissions'] = ['administrator'];

    $resolved = resolveProductionAccessGrant($grant);
    $codes = array_column($resolved['reports']['conflicts'], 'code');

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and($codes)->toContain('access_provisioned_before_approval')
        ->and($codes)->toContain('verified_permissions_mismatch');
});

test('revocation is explicit, evidenced, and cannot leave usable access', function () {
    $grant = completeProductionAccessGrant('revoked');
    $grant['revocation'] = [
        'actor' => 'Hypothetical Firm Access Authority',
        'authority_basis' => 'Production Access Policy',
        'reason' => 'Engagement access no longer required',
        'recorded_at' => '2026-08-18T10:00:00+08:00',
        'evidence_record_key' => 'EVD-ACCESS-REVOCATION',
    ];

    $resolved = resolveProductionAccessGrant($grant);

    expect($resolved['access_grants'][0]['may_use_access'])->toBeFalse()
        ->and($resolved['access_grants'][0]['operational_status'])->toBe('revoked')
        ->and($resolved['reports']['conflicts'])->toBe([]);
});

test('example', function () {
    expect(true)->toBeTrue();
});
