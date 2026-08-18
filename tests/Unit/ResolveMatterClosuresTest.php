<?php

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\MatterClosures\MatterClosureDefinition;
use App\MatterClosures\ResolveMatterClosures;
use App\MatterEvents\ResolvedMatterEvents;

function emptyMatterEvents(): ResolvedMatterEvents
{
    return new ResolvedMatterEvents(1, [], [], [], [], [], [], [], [], []);
}

function emptyCorrectiveActions(): ResolvedCorrectiveActions
{
    return new ResolvedCorrectiveActions(1, [], [], [], [], [], [], [], [], []);
}

test('an empty Matter Closure register is consistent', function () {
    $resolved = (new ResolveMatterClosures)->handle(new MatterClosureDefinition(1, [], [], []), emptyMatterEvents(), emptyCorrectiveActions())->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['closures'])->toBe(0)
        ->and($resolved['counts']['follow_up_complete'])->toBe(0);
});

test('Matter closure does not erase outstanding Corrective Actions', function () {
    $events = new ResolvedMatterEvents(1, [], [[
        'key' => 'closure-event-001',
        'matter_key' => 'migration',
        'type' => 'closure',
    ]], [[
        'key' => 'closure-event-001',
        'matter_key' => 'migration',
        'type' => 'closure',
    ]], [], [], [], [], [], [], []);
    $actions = new ResolvedCorrectiveActions(1, [], [], [[
        'key' => 'corrective-001',
        'lifecycle_status' => 'in_progress',
    ]], [], [], [], [], [], []);
    $resolved = (new ResolveMatterClosures)->handle(
        new MatterClosureDefinition(1, [], [[
            'key' => 'matter-closure-001',
            'matter_key' => 'migration',
            'closure_event_key' => 'closure-event-001',
            'corrective_action_keys' => ['corrective-001'],
            'evidence_record_key' => 'closure-evidence',
        ]], [[
            'key' => 'closure-evidence', 'record_type' => 'Matter Closure', 'subject' => 'matter-closure-001', 'actor' => 'partner', 'recorded_at' => '2026-08-18T10:00:00+08:00', 'source' => 'Closure record', 'reason' => 'Evidence closure.', 'state' => 'accepted',
        ]]),
        $events,
        $actions,
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['projections'][0]['matter_closed'])->toBeTrue()
        ->and($resolved['projections'][0]['follow_up_complete'])->toBeFalse()
        ->and($resolved['projections'][0]['outstanding_corrective_action_keys'])->toBe(['corrective-001']);
});

test('closure cannot link an unknown corrective action or non-admitted event', function () {
    $resolved = (new ResolveMatterClosures)->handle(
        new MatterClosureDefinition(1, [], [[
            'key' => 'matter-closure-002', 'matter_key' => 'migration', 'closure_event_key' => 'missing-event', 'corrective_action_keys' => ['missing-action'],
        ]], []),
        emptyMatterEvents(),
        emptyCorrectiveActions(),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['projections'])->toBeEmpty()
        ->and(array_column($resolved['reports']['event_gaps'], 'code'))->toContain('matter_closure_event_not_admitted')
        ->and(array_column($resolved['reports']['action_gaps'], 'code'))->toContain('matter_closure_corrective_action_missing');
});
