<?php

use App\BreakGlassAccess\ResolvedBreakGlassAccess;
use App\Changes\ResolvedChanges;
use App\CorrectiveActions\CorrectiveActionDefinition;
use App\CorrectiveActions\ResolveCorrectiveActions;
use App\Incidents\ResolvedIncidents;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;
use App\ProductionAccess\ResolvedProductionAccess;

function correctiveDefinitionArray(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../resources/institution/corrective-actions.json'), true, flags: JSON_THROW_ON_ERROR);
}

function correctiveEvidence(string $key): array
{
    return [
        'key' => $key,
        'record_type' => 'Corrective Action Evidence',
        'subject' => 'Hypothetical remediation',
        'actor' => 'Hypothetical institutional actor',
        'recorded_at' => '2026-08-10T10:00:00+08:00',
        'source' => 'Hypothetical control record',
        'reason' => 'Supports a distinct corrective-action fact',
        'state' => 'final',
    ];
}

function correctivePolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(file_get_contents(__DIR__.'/../../resources/institution/policies.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($effective) {
        $keys = ['authority-and-delegation', 'incident-management', 'change-management', 'production-access', 'information-security'];
        foreach ($registry['policies'] as &$policy) {
            if (! in_array($policy['key'], $keys, true)) {
                continue;
            }
            $policy['versions'][0]['status'] = 'effective';
            $policy['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
            $policy['versions'][0]['approval'] = [
                'key' => 'APR-'.strtoupper($policy['key']),
                'outcome' => 'approved',
                'approver' => 'Hypothetical Policy Authority',
                'authority_basis' => 'Hypothetical policy authority',
                'decided_at' => '2026-07-30T09:00:00+08:00',
                'evidence_record_key' => 'EVD-CA-POLICY',
            ];
        }
        unset($policy);
        $registry['evidence_records'][] = correctiveEvidence('EVD-CA-POLICY');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function correctiveIncidents(bool $closed = false): ResolvedIncidents
{
    return new ResolvedIncidents(1, [], [], [[
        'key' => 'INC-HYPOTHETICAL-0001',
        'title' => 'Hypothetical Incident',
        'lifecycle_status' => $closed ? 'closed' : 'under_review',
    ]], [], [], [], [], [], []);
}

function correctiveChanges(): ResolvedChanges
{
    return new ResolvedChanges(1, [], [], [], [], [], [], [], [], []);
}

function correctiveBreakGlass(): ResolvedBreakGlassAccess
{
    return new ResolvedBreakGlassAccess(1, [], [], [], [], [], [], [], [], []);
}

function correctiveProductionAccess(): ResolvedProductionAccess
{
    return new ResolvedProductionAccess(1, [], [], [], [], [], [], [], [], []);
}

function correctiveActionRecord(string $status = 'in_progress'): array
{
    return [
        'key' => 'CA-HYPOTHETICAL-0001',
        'title' => 'Improve identity-path resilience',
        'lifecycle_status' => $status,
        'source' => [
            'type' => 'incident',
            'key' => 'INC-HYPOTHETICAL-0001',
            'finding' => 'The ordinary identity path had a single failure mode.',
            'identified_by' => 'Hypothetical Review Facilitator',
            'found_at' => '2026-08-02T10:00:00+08:00',
            'evidence_record_key' => 'EVD-CA-SOURCE',
        ],
        'governing_requirement' => [
            'policy_key' => 'incident-management',
            'policy_version' => '0.1',
            'requirement' => 'Post-incident corrective action',
            'control' => 'Owned and independently verified remediation',
        ],
        'risk' => [
            'classification' => 'high',
            'impact' => 'Repeat loss of privileged access could delay recovery.',
            'residual_risk_if_delayed' => 'The single failure mode remains exposed.',
            'risk_owner' => 'Hypothetical Responsible Partner',
        ],
        'owner' => [
            'key' => 'technical-lead',
            'name' => 'Hypothetical Technical Lead',
            'role' => 'Technical Lead',
        ],
        'assignment' => [
            'assigned_by' => 'Hypothetical Responsible Partner',
            'authority_basis' => 'Incident Management Policy and Authority Matrix',
            'accepted_by' => 'Hypothetical Technical Lead',
            'assigned_at' => '2026-08-03T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CA-ASSIGNMENT',
        ],
        'remediation_plan' => [
            'outcome' => 'A tested alternate identity path is available.',
            'acceptance_criteria' => ['Independent restore test succeeds'],
            'steps' => ['Design alternate path', 'Implement controls', 'Test recovery'],
            'dependencies' => ['Client identity authority'],
        ],
        'due_date_history' => [[
            'effective_due_at' => '2026-08-15T17:00:00+08:00',
            'changed_by' => 'Hypothetical Responsible Partner',
            'authority_basis' => 'Corrective action assignment authority',
            'reason' => 'Initial risk-based due date',
            'changed_at' => '2026-08-03T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CA-DUE',
        ]],
        'progress_updates' => [[
            'key' => 'CA-PROGRESS-001',
            'actor' => 'Hypothetical Technical Lead',
            'update' => 'Alternate-path design completed; implementation is in progress.',
            'recorded_at' => '2026-08-08T10:00:00+08:00',
            'evidence_record_key' => 'EVD-CA-PROGRESS',
        ]],
    ];
}

function resolveCorrectiveAction(array $record, bool $sourceClosed = false, string $asOf = '2026-08-18T09:00:00+08:00'): array
{
    $definition = correctiveDefinitionArray();
    $definition['corrective_actions'] = [$record];
    foreach (['SOURCE', 'ASSIGNMENT', 'DUE', 'PROGRESS', 'ESCALATION', 'COMPLETION', 'VERIFICATION', 'CLOSURE'] as $suffix) {
        $definition['evidence_records'][] = correctiveEvidence("EVD-CA-{$suffix}");
    }

    return (new ResolveCorrectiveActions)->handle(
        CorrectiveActionDefinition::fromArray($definition),
        correctiveIncidents($sourceClosed),
        correctiveChanges(),
        correctiveBreakGlass(),
        correctiveProductionAccess(),
        correctivePolicies(),
        new DateTimeImmutable($asOf),
    )->toArray();
}

test('canonical Corrective Action Register remains honest about assignment readiness', function () {
    $resolved = (new ResolveCorrectiveActions)->handle(
        CorrectiveActionDefinition::fromArray(correctiveDefinitionArray()),
        correctiveIncidents(),
        correctiveChanges(),
        correctiveBreakGlass(),
        correctiveProductionAccess(),
        correctivePolicies(false),
        new DateTimeImmutable('2026-08-18T09:00:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['corrective_actions'])->toBe(0)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(1);
});

test('source closure does not close or erase corrective work', function () {
    $resolved = resolveCorrectiveAction(correctiveActionRecord(), true, '2026-08-10T09:00:00+08:00');

    expect($resolved['corrective_actions'][0]['source_resolved'])->toBeTrue()
        ->and($resolved['corrective_actions'][0]['lifecycle_status'])->toBe('in_progress')
        ->and($resolved['corrective_actions'][0]['operational_status'])->toBe('active');
});

test('overdue corrective work requires explicit evidenced escalation', function () {
    $resolved = resolveCorrectiveAction(correctiveActionRecord());

    expect($resolved['corrective_actions'][0]['overdue'])->toBeTrue()
        ->and($resolved['corrective_actions'][0]['escalation_current'])->toBeFalse()
        ->and($resolved['reports']['decision_gaps'])->toContainEqual([
            'code' => 'overdue_corrective_action_not_escalated',
            'message' => 'Improve identity-path resilience is overdue without an explicit escalation record.',
        ]);
});

test('a revised due date preserves the earlier commitment and becomes current only through evidenced history', function () {
    $record = correctiveActionRecord();
    $record['due_date_history'][] = [
        'effective_due_at' => '2026-08-30T17:00:00+08:00',
        'changed_by' => 'Hypothetical Responsible Partner',
        'authority_basis' => 'Corrective action extension authority',
        'reason' => 'Client identity authority scheduled the required test window.',
        'changed_at' => '2026-08-12T09:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-DUE',
    ];

    $resolved = resolveCorrectiveAction($record);

    expect($resolved['corrective_actions'][0]['current_due_at'])->toBe('2026-08-30T17:00:00+08:00')
        ->and($resolved['corrective_actions'][0]['due_date_history'])->toHaveCount(2)
        ->and($resolved['corrective_actions'][0]['overdue'])->toBeFalse();
});

test('an accountable owner cannot independently verify their own completion claim', function () {
    $record = correctiveActionRecord('closed');
    $record['completion_claim'] = [
        'claimed_by_key' => 'technical-lead',
        'claimed_by' => 'Hypothetical Technical Lead',
        'summary' => 'Alternate identity path implemented and tested.',
        'claimed_at' => '2026-08-14T14:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-COMPLETION',
    ];
    $record['verification'] = [
        'verified_by_key' => 'technical-lead',
        'verified_by' => 'Hypothetical Technical Lead',
        'verification_standard' => 'Independent restore test succeeds',
        'observed_result' => 'Restore test succeeded.',
        'outcome' => 'verified',
        'verified_at' => '2026-08-14T15:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-VERIFICATION',
    ];
    $record['closure'] = [
        'closed_by' => 'Hypothetical Responsible Partner',
        'authority_basis' => 'Incident Management Policy',
        'reason' => 'Verified outcome accepted.',
        'closed_at' => '2026-08-14T16:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-CLOSURE',
    ];

    $resolved = resolveCorrectiveAction($record, true, '2026-08-14T17:00:00+08:00');

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['corrective_actions'][0]['operational_status'])->toBe('blocked_closure')
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain('self_verified_corrective_action');
});

test('verification makes closure eligible but does not itself close the action', function () {
    $record = correctiveActionRecord('verified');
    $record['completion_claim'] = [
        'claimed_by_key' => 'technical-lead',
        'claimed_by' => 'Hypothetical Technical Lead',
        'summary' => 'Alternate identity path implemented and tested.',
        'claimed_at' => '2026-08-14T14:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-COMPLETION',
    ];
    $record['verification'] = [
        'verified_by_key' => 'independent-verifier',
        'verified_by' => 'Hypothetical Independent Verifier',
        'verification_standard' => 'Independent restore test succeeds',
        'observed_result' => 'Restore test succeeded.',
        'outcome' => 'verified',
        'verified_at' => '2026-08-14T15:00:00+08:00',
        'evidence_record_key' => 'EVD-CA-VERIFICATION',
    ];

    $resolved = resolveCorrectiveAction($record, true, '2026-08-14T17:00:00+08:00');

    expect($resolved['corrective_actions'][0]['may_close_corrective_action'])->toBeTrue()
        ->and($resolved['corrective_actions'][0]['operational_status'])->toBe('ready_for_closure')
        ->and($resolved['counts']['ready_for_closure'])->toBe(1);
});
