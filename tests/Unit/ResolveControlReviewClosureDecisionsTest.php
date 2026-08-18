<?php

use App\ControlClosures\ResolvedControlReviewClosureEligibility;
use App\ControlDecisions\ControlReviewClosureDecisionDefinition;
use App\ControlDecisions\ResolveControlReviewClosureDecisions;

function eligibilityForClosureDecisions(bool $eligible = true): ResolvedControlReviewClosureEligibility
{
    return new ResolvedControlReviewClosureEligibility(
        schemaVersion: 1,
        requirements: [],
        reviews: [],
        eligibilityReviews: [[
            'key' => 'eligibility-001',
            'action_key' => 'action-001',
            'closure_eligible' => $eligible,
            'closure_decision_issued' => false,
        ]],
        conflicts: [],
        eligibilityGaps: [],
        evidenceGaps: [],
    );
}

test('an empty closure decision definition is consistent', function () {
    $resolved = app(ResolveControlReviewClosureDecisions::class)->handle(
        new ControlReviewClosureDecisionDefinition(1, [], []),
        eligibilityForClosureDecisions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedDecisions)->toHaveCount(0);
});

test('an eligible action admits a separate closure decision without mutating the action', function () {
    $resolved = app(ResolveControlReviewClosureDecisions::class)->handle(
        new ControlReviewClosureDecisionDefinition(1, [], [[
            'key' => 'decision-001',
            'eligibility_review_key' => 'eligibility-001',
            'decision' => 'closed',
            'decided_by' => 'managing-partner',
            'decided_at' => '2026-08-19T12:00:00+08:00',
            'authority_basis' => 'Managing Partner closure decision authority',
            'reason' => 'All closure prerequisites independently verified.',
            'evidence_record_key' => 'evidence-decision-001',
        ]]),
        eligibilityForClosureDecisions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedDecisions[0]['decision_resolved'])->toBeTrue()
        ->and($resolved->resolvedDecisions[0]['closure_admitted'])->toBeTrue();
});

test('closure cannot be admitted when eligibility is unresolved', function () {
    $resolved = app(ResolveControlReviewClosureDecisions::class)->handle(
        new ControlReviewClosureDecisionDefinition(1, [], [[
            'key' => 'decision-002',
            'eligibility_review_key' => 'eligibility-001',
            'decision' => 'closed',
            'decided_by' => 'managing-partner',
            'decided_at' => '2026-08-19T12:00:00+08:00',
            'authority_basis' => 'Managing Partner closure decision authority',
            'reason' => 'Attempted closure.',
            'evidence_record_key' => 'evidence-decision-002',
        ]]),
        eligibilityForClosureDecisions(false),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('conflict_detected')
        ->and(array_column($resolved->conflicts, 'code'))->toContain('closure_decision_without_eligibility')
        ->and($resolved->resolvedDecisions)->toHaveCount(0);
});
