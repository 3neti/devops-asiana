<?php

use App\ControlActions\ResolvedControlReviewActions;
use App\ControlOutcomes\ControlReviewActionOutcomeDefinition;
use App\ControlOutcomes\ResolveControlReviewActionOutcomes;

function controlActionsForOutcomes(): ResolvedControlReviewActions
{
    return new ResolvedControlReviewActions(
        schemaVersion: 1,
        requirements: [],
        actions: [],
        resolvedActions: [[
            'key' => 'action-001',
            'owner' => 'security-practice',
            'action_resolved' => true,
        ]],
        conflicts: [],
        actionGaps: [],
        evidenceGaps: [],
    );
}

test('an empty action outcome definition is consistent', function () {
    $resolved = app(ResolveControlReviewActionOutcomes::class)->handle(
        new ControlReviewActionOutcomeDefinition(1, [], []),
        controlActionsForOutcomes(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedOutcomes)->toHaveCount(0);
});

test('progress and verification references resolve without implying closure', function () {
    $resolved = app(ResolveControlReviewActionOutcomes::class)->handle(
        new ControlReviewActionOutcomeDefinition(1, [], [
            [
                'key' => 'outcome-001',
                'action_key' => 'action-001',
                'outcome_type' => 'progress',
                'actor' => 'security-practice',
                'occurred_at' => '2026-08-19T09:00:00+08:00',
                'summary' => 'Reviewed source records.',
                'evidence_record_key' => 'evidence-outcome-001',
            ],
            [
                'key' => 'outcome-002',
                'action_key' => 'action-001',
                'outcome_type' => 'verification_reference',
                'actor' => 'independent-reviewer',
                'occurred_at' => '2026-08-19T10:00:00+08:00',
                'summary' => 'Independent verification reference recorded.',
                'verified_by' => 'independent-reviewer',
                'verification_outcome' => 'verified',
                'evidence_record_key' => 'evidence-outcome-002',
            ],
        ]),
        controlActionsForOutcomes(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedOutcomes)->toHaveCount(2)
        ->and($resolved->resolvedOutcomes[0]['completion_or_closure_inferred'])->toBeFalse();
});

test('completion claims cannot be self-verified or omit evidence', function () {
    $resolved = app(ResolveControlReviewActionOutcomes::class)->handle(
        new ControlReviewActionOutcomeDefinition(1, [], [[
            'key' => 'outcome-003',
            'action_key' => 'action-001',
            'outcome_type' => 'verification_reference',
            'actor' => 'security-practice',
            'occurred_at' => '2026-08-19T11:00:00+08:00',
            'summary' => 'Owner reports verification.',
            'verified_by' => 'security-practice',
            'verification_outcome' => 'verified',
        ]]),
        controlActionsForOutcomes(),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('conflict_detected')
        ->and(array_column($resolved->conflicts, 'code'))->toContain('self_verified_control_review_action')
        ->and(array_column($resolved->evidenceGaps, 'code'))->toContain('missing_control_review_outcome_evidence');
});
