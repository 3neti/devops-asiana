<?php

use App\ControlActions\ResolvedControlReviewActions;
use App\ControlClosures\ControlReviewClosureEligibilityDefinition;
use App\ControlClosures\ResolveControlReviewClosureEligibility;
use App\ControlOutcomes\ResolvedControlReviewActionOutcomes;

function actionsForClosureEligibility(): ResolvedControlReviewActions
{
    return new ResolvedControlReviewActions(1, [], [], [[
        'key' => 'action-001',
        'owner' => 'security-practice',
        'action_resolved' => true,
    ]], [], [], [], []);
}

function outcomesForClosureEligibility(): ResolvedControlReviewActionOutcomes
{
    return new ResolvedControlReviewActionOutcomes(1, [], [], [
        [
            'key' => 'outcome-completion',
            'action_key' => 'action-001',
            'outcome_type' => 'completion_claim',
            'occurred_at' => '2026-08-19T09:00:00+08:00',
        ],
        [
            'key' => 'outcome-verification',
            'action_key' => 'action-001',
            'outcome_type' => 'verification_reference',
            'occurred_at' => '2026-08-19T10:00:00+08:00',
            'verification_outcome' => 'verified',
        ],
    ], [], [], []);
}

test('an empty closure eligibility definition is consistent', function () {
    $resolved = app(ResolveControlReviewClosureEligibility::class)->handle(
        new ControlReviewClosureEligibilityDefinition(1, [], []),
        actionsForClosureEligibility(),
        outcomesForClosureEligibility(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->eligibilityReviews)->toHaveCount(0);
});

test('eligibility requires completion, independent verification, authority, and evidence', function () {
    $resolved = app(ResolveControlReviewClosureEligibility::class)->handle(
        new ControlReviewClosureEligibilityDefinition(1, [], [[
            'key' => 'eligibility-001',
            'action_key' => 'action-001',
            'reviewed_by' => 'managing-partner',
            'reviewed_at' => '2026-08-19T11:00:00+08:00',
            'closure_authority_basis' => 'Managing Partner closure review authority',
            'closure_evidence_record_key' => 'evidence-closure-001',
        ]]),
        actionsForClosureEligibility(),
        outcomesForClosureEligibility(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->eligibilityReviews[0]['closure_eligible'])->toBeTrue()
        ->and($resolved->eligibilityReviews[0]['closure_decision_issued'])->toBeFalse();
});

test('missing prerequisites remain visible and never become eligible', function () {
    $resolved = app(ResolveControlReviewClosureEligibility::class)->handle(
        new ControlReviewClosureEligibilityDefinition(1, [], [[
            'key' => 'eligibility-002',
            'action_key' => 'action-001',
            'reviewed_by' => 'managing-partner',
        ]]),
        actionsForClosureEligibility(),
        new ResolvedControlReviewActionOutcomes(1, [], [], [], [], [], []),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved->eligibilityReviews[0]['closure_eligible'])->toBeFalse()
        ->and(array_column($resolved->eligibilityGaps, 'code'))->toContain('missing_completion_claim', 'missing_independent_verification', 'incomplete_closure_eligibility_review')
        ->and(array_column($resolved->evidenceGaps, 'code'))->toContain('missing_closure_eligibility_evidence');
});
