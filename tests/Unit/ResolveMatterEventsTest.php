<?php

use App\MatterEvents\MatterEventDefinition;
use App\MatterEvents\ResolveMatterEvents;
use App\Matters\ResolvedMatters;

function emptyMatterResolution(): ResolvedMatters
{
    return new ResolvedMatters(1, [], [], [], [], [], [], [], [], [], [], []);
}

test('an empty Matter Event register is consistent', function () {
    $resolved = (new ResolveMatterEvents)->handle(new MatterEventDefinition(1, [], [], []), emptyMatterResolution())->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['events'])->toBe(0)
        ->and($resolved['counts']['admitted_events'])->toBe(0);
});

test('a Matter Event cannot exist without a parent and evidence', function () {
    $resolved = (new ResolveMatterEvents)->handle(
        new MatterEventDefinition(1, [], [[
            'key' => 'review-001',
            'matter_key' => 'production-migration',
            'type' => 'review',
            'status' => 'recorded',
            'actor_key' => 'angelica-santos',
            'summary' => 'Review the Matter state.',
            'occurred_at' => '2026-08-18T10:00:00+08:00',
        ]], []),
        emptyMatterResolution(),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['events'][0]['admitted'])->toBeFalse()
        ->and(array_column($resolved['reports']['matter_gaps'], 'code'))->toContain('matter_event_parent_missing')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))->toContain('matter_event_evidence_missing');
});

test('closure requires a different independent verifier', function () {
    $matters = new ResolvedMatters(1, [], [[
        'key' => 'production-migration',
        'title' => 'Production migration',
    ]], [], [], [], [], [], [], [], [], []);
    $resolved = (new ResolveMatterEvents)->handle(
        new MatterEventDefinition(1, [], [[
            'key' => 'closure-001',
            'matter_key' => 'production-migration',
            'type' => 'closure',
            'status' => 'recorded',
            'actor_key' => 'angelica-santos',
            'verified_by_key' => 'angelica-santos',
            'disposition' => 'closed',
            'summary' => 'Close Matter.',
            'occurred_at' => '2026-08-18T10:00:00+08:00',
        ]], []),
        $matters,
    )->toArray();

    expect($resolved['events'][0]['admitted'])->toBeFalse()
        ->and(array_column($resolved['reports']['event_gaps'], 'code'))->toContain('matter_closure_not_independently_verified');
});
