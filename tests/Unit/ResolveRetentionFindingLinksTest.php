<?php

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\RetentionFindings\ResolveRetentionFindingLinks;
use App\RetentionFindings\RetentionFindingLinkDefinition;
use App\RetentionReviews\ResolvedRetentionReviews;

function retentionReviewsForLinking(): ResolvedRetentionReviews
{
    return new ResolvedRetentionReviews(
        schemaVersion: 1,
        requirements: [],
        reviews: [[
            'key' => 'review-001',
            'outcome' => 'disposition_due',
            'review_resolved' => true,
            'evidence_record_key' => 'evidence-001',
        ]],
        resolvedReviews: [],
        conflicts: [],
        reviewGaps: [],
        exceptionGaps: [],
        evidenceGaps: [],
    );
}

function correctiveActionsForLinking(): ResolvedCorrectiveActions
{
    return new ResolvedCorrectiveActions(
        schemaVersion: 1,
        governingPolicies: [],
        recordRequirements: [],
        correctiveActions: [['key' => 'action-001', 'title' => 'Review retention disposition']],
        evidenceRecords: [],
        lifecycleCounts: [],
        conflicts: [],
        decisionGaps: [],
        evidenceGaps: [],
        readinessGaps: [],
    );
}

test('an empty retention finding link definition is consistent', function () {
    $resolved = app(ResolveRetentionFindingLinks::class)->handle(
        new RetentionFindingLinkDefinition(1, [], []),
        retentionReviewsForLinking(),
        correctiveActionsForLinking(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedLinks)->toHaveCount(0);
});

test('a finding link resolves against a resolved review and existing corrective action', function () {
    $resolved = app(ResolveRetentionFindingLinks::class)->handle(
        new RetentionFindingLinkDefinition(1, [], [[
            'key' => 'link-001',
            'retention_review_key' => 'review-001',
            'corrective_action_key' => 'action-001',
            'linked_by' => 'managing-partner',
            'linked_at' => '2026-08-18T13:00:00+08:00',
            'reason' => 'Disposition finding requires tracked remediation.',
            'evidence_record_key' => 'evidence-001',
        ]]),
        retentionReviewsForLinking(),
        correctiveActionsForLinking(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedLinks)->toHaveCount(1)
        ->and($resolved->resolvedLinks[0]['link_resolved'])->toBeTrue();
});

test('finding links preserve gaps for unresolved reviews and unknown actions', function () {
    $resolved = app(ResolveRetentionFindingLinks::class)->handle(
        new RetentionFindingLinkDefinition(1, [], [[
            'key' => 'link-002',
            'retention_review_key' => 'review-missing',
            'corrective_action_key' => 'action-missing',
            'linked_by' => 'managing-partner',
            'linked_at' => '2026-08-18T13:00:00+08:00',
            'reason' => 'Unresolved finding.',
            'evidence_record_key' => 'evidence-missing',
        ]]),
        retentionReviewsForLinking(),
        correctiveActionsForLinking(),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved->resolvedLinks)->toHaveCount(0)
        ->and(array_column($resolved->findingGaps, 'code'))->toContain('retention_review_not_found')
        ->and(array_column($resolved->actionGaps, 'code'))->toContain('corrective_action_not_found')
        ->and(array_column($resolved->evidenceGaps, 'code'))->toContain('retention_finding_link_evidence_not_reviewed');
});
