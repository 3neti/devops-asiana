<?php

use App\ControlClosures\ResolvedControlReviewClosureEligibility;
use App\ControlDecisions\ResolvedControlReviewClosureDecisions;
use App\ControlReconciliation\ControlReviewClosureReconciliationDefinition;
use App\ControlReconciliation\ResolveControlReviewClosureReconciliations;

function decisionsForReconciliation(): ResolvedControlReviewClosureDecisions
{
    return new ResolvedControlReviewClosureDecisions(1, [], [], [[
        'key' => 'decision-001',
        'eligibility_review_key' => 'eligibility-001',
        'closure_admitted' => true,
        'decision_resolved' => true,
    ]], [], [], []);
}

function eligibilityForReconciliation(): ResolvedControlReviewClosureEligibility
{
    return new ResolvedControlReviewClosureEligibility(1, [], [], [[
        'key' => 'eligibility-001',
        'action_key' => 'action-001',
        'closure_eligible' => true,
    ]], [], [], []);
}

test('an empty reconciliation definition is consistent', function () {
    $resolved = app(ResolveControlReviewClosureReconciliations::class)->handle(
        new ControlReviewClosureReconciliationDefinition(1, [], []),
        decisionsForReconciliation(),
        eligibilityForReconciliation(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedReconciliations)->toHaveCount(0);
});

test('matching downstream state reconciles without mutating sources', function () {
    $resolved = app(ResolveControlReviewClosureReconciliations::class)->handle(
        new ControlReviewClosureReconciliationDefinition(1, [], [[
            'key' => 'reconciliation-001',
            'decision_key' => 'decision-001',
            'downstream_state' => 'closed',
            'reconciled_by' => 'managing-partner',
            'reconciled_at' => '2026-08-19T13:00:00+08:00',
            'basis' => 'Compared admitted decision to downstream action register.',
            'evidence_record_key' => 'evidence-reconciliation-001',
        ]]),
        decisionsForReconciliation(),
        eligibilityForReconciliation(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedReconciliations[0]['reconciled'])->toBeTrue()
        ->and($resolved->resolvedReconciliations[0]['source_mutated'])->toBeFalse();
});

test('state discrepancies remain explicit', function () {
    $resolved = app(ResolveControlReviewClosureReconciliations::class)->handle(
        new ControlReviewClosureReconciliationDefinition(1, [], [[
            'key' => 'reconciliation-002',
            'decision_key' => 'decision-001',
            'downstream_state' => 'open',
            'reconciled_by' => 'managing-partner',
            'reconciled_at' => '2026-08-19T13:00:00+08:00',
            'basis' => 'Compared admitted decision to downstream action register.',
            'evidence_record_key' => 'evidence-reconciliation-002',
        ]]),
        decisionsForReconciliation(),
        eligibilityForReconciliation(),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved->resolvedReconciliations[0]['reconciled'])->toBeFalse()
        ->and(array_column($resolved->reconciliationGaps, 'code'))->toContain('closure_state_discrepancy');
});
