<?php

use App\Continuity\ContinuityExerciseDefinition;
use App\Continuity\ResolveContinuityExercises;
use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;

function continuityDefinitionArray(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../resources/institution/continuity-exercises.json'), true, flags: JSON_THROW_ON_ERROR);
}

function continuityEvidence(string $key): array
{
    return [
        'key' => $key,
        'record_type' => 'Continuity Exercise Evidence',
        'subject' => 'Hypothetical restore exercise',
        'actor' => 'Hypothetical institutional actor',
        'recorded_at' => '2026-08-18T12:00:00+08:00',
        'source' => 'Hypothetical exercise record',
        'reason' => 'Supports a distinct continuity fact',
        'state' => 'final',
    ];
}

function continuityPolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(file_get_contents(__DIR__.'/../../resources/institution/policies.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($effective) {
        $keys = ['business-continuity-dr', 'authority-and-delegation', 'information-security'];
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
                'evidence_record_key' => 'EVD-CE-POLICY',
            ];
        }
        unset($policy);
        $registry['evidence_records'][] = continuityEvidence('EVD-CE-POLICY');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function continuityEngagements(): ResolvedEngagements
{
    return new ResolvedEngagements(1, [], [], [], [], [], [], [], [], []);
}

function continuityCorrectiveActions(bool $includeAction = true): ResolvedCorrectiveActions
{
    return new ResolvedCorrectiveActions(
        1,
        [],
        [],
        $includeAction ? [['key' => 'CA-HYPOTHETICAL-0001', 'title' => 'Improve restore automation']] : [],
        [],
        [],
        [],
        [],
        [],
        [],
    );
}

function continuityExerciseRecord(string $status = 'scheduled'): array
{
    return [
        'key' => 'CE-HYPOTHETICAL-0001',
        'title' => 'Hypothetical service restore exercise',
        'lifecycle_status' => $status,
        'exercise_type' => 'backup_restore',
        'scope' => [
            'context' => 'firm',
            'services' => ['Institutional evidence registry'],
            'systems' => ['Hypothetical evidence database'],
            'environments' => ['Isolated recovery environment'],
            'data_classification' => 'Firm Confidential synthetic test data',
            'exclusions' => ['Production changes', 'Client data'],
            'scope_owner' => 'Hypothetical Continuity Owner',
        ],
        'recovery_objectives' => [[
            'key' => 'OBJ-EVIDENCE-REGISTRY',
            'service' => 'Institutional evidence registry',
            'rto_seconds' => 3600,
            'rpo_seconds' => 900,
            'source' => 'Hypothetical approved Firm continuity plan',
            'approved_by' => 'Hypothetical Managing Partner',
            'approved_at' => '2026-08-01T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CE-OBJECTIVE',
        ]],
        'dependencies' => [[
            'key' => 'DEP-CLOUD-STORAGE',
            'name' => 'Hypothetical backup storage',
            'type' => 'cloud_provider',
            'owner' => 'Hypothetical Continuity Owner',
            'failure_impact' => 'Recovery point cannot be retrieved.',
            'recovery_role' => 'Stores encrypted backup artifacts.',
        ]],
        'exercise_plan' => [
            'coordinator_key' => 'continuity-coordinator',
            'coordinator' => 'Hypothetical Continuity Coordinator',
            'scenario' => 'Primary evidence database is unavailable.',
            'participants' => ['Hypothetical Restore Engineer', 'Hypothetical Observer'],
            'success_criteria' => ['Restore completes', 'Integrity checks pass', 'Objectives measured'],
            'communications' => 'Internal exercise channel and Managing Partner summary',
            'safe_execution_boundary' => [
                'production_changes_prohibited' => true,
                'test_data_disposition' => 'Synthetic data disposed after verification',
            ],
        ],
        'approval' => [
            'outcome' => 'approved',
            'approved_by' => 'Hypothetical Managing Partner',
            'authority_basis' => 'Continuity Exercise authority',
            'scope_digest' => 'sha256:hypothetical-scope',
            'risk_accepted' => 'Bounded isolated restore risk',
            'approved_at' => '2026-08-10T09:00:00+08:00',
            'evidence_record_key' => 'EVD-CE-APPROVAL',
        ],
        'backup_baseline' => [
            'backup_set' => 'Hypothetical encrypted database backup',
            'owner' => 'Hypothetical Continuity Owner',
            'scope' => 'Evidence registry database and schema',
            'storage_boundary' => 'Hypothetical secondary cloud account',
            'encrypted' => true,
            'retention' => 'Hypothetical approved retention schedule',
            'last_successful_at' => '2026-08-18T08:00:00+08:00',
            'recovery_point_at' => '2026-08-18T08:00:00+08:00',
            'integrity_check' => 'Provider checksum passed; restorability not inferred',
            'evidence_record_key' => 'EVD-CE-BACKUP',
        ],
        'schedule' => [
            'starts_at' => '2026-08-18T09:00:00+08:00',
            'ends_at' => '2026-08-18T13:00:00+08:00',
            'timezone' => 'Asia/Manila',
        ],
    ];
}

function executedContinuityExercise(string $status = 'verified'): array
{
    $record = continuityExerciseRecord($status);
    $record['execution'] = [
        'coordinator_key' => 'continuity-coordinator',
        'coordinator' => 'Hypothetical Continuity Coordinator',
        'started_at' => '2026-08-18T09:00:00+08:00',
        'completed_at' => '2026-08-18T10:30:00+08:00',
        'timeline' => [
            ['at' => '2026-08-18T09:00:00+08:00', 'actor' => 'Restore Engineer', 'action' => 'Restore started'],
            ['at' => '2026-08-18T10:30:00+08:00', 'actor' => 'Restore Engineer', 'action' => 'Integrity checks completed'],
        ],
        'evidence_record_key' => 'EVD-CE-EXECUTION',
    ];
    $record['restore_result'] = [
        'target' => 'Isolated recovery environment',
        'isolated_environment' => true,
        'recovery_point_used_at' => '2026-08-18T08:00:00+08:00',
        'integrity_result' => 'Application and record checks passed.',
        'security_result' => 'Access and encryption controls verified.',
        'observations' => [[
            'objective_key' => 'OBJ-EVIDENCE-REGISTRY',
            'observed_recovery_time_seconds' => 5400,
            'observed_recovery_point_age_seconds' => 3600,
            'observed_result' => 'Restore succeeded but exceeded both objectives.',
            'evidence_record_key' => 'EVD-CE-OBSERVATION',
        ]],
        'test_data_disposition' => 'disposed',
        'evidence_record_key' => 'EVD-CE-RESTORE',
    ];
    $record['verification'] = [
        'verified_by_key' => 'independent-verifier',
        'verified_by' => 'Hypothetical Independent Verifier',
        'standard' => 'Approved exercise criteria and recovery objectives',
        'outcome' => 'partial',
        'summary' => 'Restore succeeded; both objectives were missed.',
        'no_material_gaps' => false,
        'gaps' => [[
            'key' => 'GAP-RESTORE-AUTOMATION',
            'finding' => 'Manual recovery steps exceeded the RTO.',
            'impact' => 'Recovery may exceed the approved objective.',
            'corrective_action_key' => 'CA-HYPOTHETICAL-0001',
        ]],
        'verified_at' => '2026-08-18T11:00:00+08:00',
        'evidence_record_key' => 'EVD-CE-VERIFICATION',
    ];

    return $record;
}

function resolveContinuityExercise(array $record, bool $includeCorrectiveAction = true, string $asOf = '2026-08-18T10:00:00+08:00'): array
{
    $definition = continuityDefinitionArray();
    $definition['exercise_records'] = [$record];
    foreach (['OBJECTIVE', 'APPROVAL', 'BACKUP', 'EXECUTION', 'RESTORE', 'OBSERVATION', 'VERIFICATION', 'CLOSURE'] as $suffix) {
        $definition['evidence_records'][] = continuityEvidence("EVD-CE-{$suffix}");
    }

    return (new ResolveContinuityExercises)->handle(
        ContinuityExerciseDefinition::fromArray($definition),
        continuityEngagements(),
        continuityCorrectiveActions($includeCorrectiveAction),
        continuityPolicies(),
        new DateTimeImmutable($asOf),
    )->toArray();
}

test('canonical Continuity Exercise Register does not invent objectives or readiness', function () {
    $resolved = (new ResolveContinuityExercises)->handle(
        ContinuityExerciseDefinition::fromArray(continuityDefinitionArray()),
        continuityEngagements(),
        continuityCorrectiveActions(false),
        continuityPolicies(false),
        new DateTimeImmutable('2026-08-18T10:00:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['exercise_records'])->toBe(0)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(3);
});

test('a complete scheduled exercise is executable only inside its approved window', function () {
    $resolved = resolveContinuityExercise(continuityExerciseRecord());

    expect($resolved['exercise_records'][0]['may_execute_exercise'])->toBeTrue()
        ->and($resolved['exercise_records'][0]['operational_status'])->toBe('authorized_for_execution');
});

test('backup success does not substitute for a restore result', function () {
    $record = continuityExerciseRecord('awaiting_verification');
    $record['execution'] = executedContinuityExercise()['execution'];
    $resolved = resolveContinuityExercise($record);

    expect($resolved['exercise_records'][0]['objectives_not_measured'])->toBe(1)
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('missing_restore_result', 'unmeasured_recovery_objective');
});

test('observed recovery facts are compared with approved objectives and gaps link to corrective action', function () {
    $resolved = resolveContinuityExercise(executedContinuityExercise(), true, '2026-08-18T12:00:00+08:00');

    expect($resolved['exercise_records'][0]['objectives_missed'])->toBe(1)
        ->and($resolved['exercise_records'][0]['unresolved_gaps'])->toBe(0)
        ->and($resolved['exercise_records'][0]['may_close_exercise'])->toBeTrue()
        ->and($resolved['exercise_records'][0]['operational_status'])->toBe('ready_for_closure');
});

test('a material exercise gap cannot disappear without a canonical corrective action', function () {
    $resolved = resolveContinuityExercise(executedContinuityExercise(), false, '2026-08-18T12:00:00+08:00');

    expect($resolved['exercise_records'][0]['unresolved_gaps'])->toBe(1)
        ->and($resolved['exercise_records'][0]['may_close_exercise'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('unaccountable_continuity_gap');
});

test('a missed recovery objective cannot be declared gap-free', function () {
    $record = executedContinuityExercise();
    $record['verification']['outcome'] = 'passed';
    $record['verification']['no_material_gaps'] = true;
    $record['verification']['gaps'] = [];
    $resolved = resolveContinuityExercise($record, true, '2026-08-18T12:00:00+08:00');

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['exercise_records'][0]['may_close_exercise'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain('missed_objective_without_gap');
});

test('the exercise coordinator cannot independently verify the exercise', function () {
    $record = executedContinuityExercise();
    $record['verification']['verified_by_key'] = 'continuity-coordinator';
    $resolved = resolveContinuityExercise($record, true, '2026-08-18T12:00:00+08:00');

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['exercise_records'][0]['may_close_exercise'])->toBeFalse()
        ->and(array_column($resolved['reports']['conflicts'], 'code'))->toContain('self_verified_continuity_exercise');
});
