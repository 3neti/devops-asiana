<?php

use App\ControlActions\ControlReviewActionDefinition;
use App\ControlActions\ResolveControlReviewActions;
use App\ControlSignoffs\ResolvedControlReviewSignoffs;

function controlSignoffsForActions(): ResolvedControlReviewSignoffs
{
    return new ResolvedControlReviewSignoffs(
        schemaVersion: 1,
        requirements: [],
        signoffs: [],
        resolvedSignoffs: [[
            'key' => 'signoff-001',
            'reviewed_control_keys' => ['evidence-custody'],
            'signoff_resolved' => true,
        ]],
        conflicts: [],
        reviewGaps: [],
        evidenceGaps: [],
    );
}

test('an empty control action definition is consistent', function () {
    $resolved = app(ResolveControlReviewActions::class)->handle(
        new ControlReviewActionDefinition(1, [], []),
        controlSignoffsForActions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedActions)->toHaveCount(0);
});

test('an action resolves against an admitted sign-off and bounded control scope', function () {
    $resolved = app(ResolveControlReviewActions::class)->handle(
        new ControlReviewActionDefinition(1, [], [[
            'key' => 'action-001',
            'signoff_key' => 'signoff-001',
            'control_key' => 'evidence-custody',
            'action_type' => 'investigate',
            'owner' => 'security-practice',
            'due_at' => '2026-09-01T00:00:00+08:00',
            'authority_basis' => 'Managing Partner action register authority',
            'reason' => 'Investigate custody source gap.',
            'evidence_record_key' => 'evidence-action-001',
        ]]),
        controlSignoffsForActions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedActions)->toHaveCount(1)
        ->and($resolved->resolvedActions[0]['action_resolved'])->toBeTrue();
});

test('actions cannot escape sign-off scope or omit accountability', function () {
    $resolved = app(ResolveControlReviewActions::class)->handle(
        new ControlReviewActionDefinition(1, [], [[
            'key' => 'action-002',
            'signoff_key' => 'signoff-001',
            'control_key' => 'retention-reviews',
            'action_type' => 'remediate',
        ]]),
        controlSignoffsForActions(),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved->resolvedActions)->toHaveCount(0)
        ->and(array_column($resolved->actionGaps, 'code'))->toContain('action_control_not_reviewed', 'incomplete_control_review_action')
        ->and(array_column($resolved->evidenceGaps, 'code'))->toContain('missing_control_review_action_evidence');
});
