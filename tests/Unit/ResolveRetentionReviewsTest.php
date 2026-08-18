<?php

use App\EvidenceCustody\ResolvedEvidenceCustody;
use App\Policies\ResolvedPolicyRegistry;
use App\RetentionReviews\ResolveRetentionReviews;
use App\RetentionReviews\RetentionReviewDefinition;

function retentionCustody(): ResolvedEvidenceCustody
{
    return new ResolvedEvidenceCustody(
        schemaVersion: 1,
        requirements: [],
        records: [[
            'key' => 'custody-001',
            'evidence_key' => 'evidence-001',
        ]],
        resolvedRecords: [],
        conflicts: [],
        sourceGaps: [],
        custodyGaps: [],
        retentionGaps: [],
        integrityGaps: [],
        dispositionGaps: [],
    );
}

function retentionPolicies(array $exceptions = []): ResolvedPolicyRegistry
{
    return new ResolvedPolicyRegistry(
        schemaVersion: 1,
        policies: [],
        exceptions: $exceptions,
        evidenceRecords: [],
        statusCounts: [],
        conflicts: [],
        lifecycleGaps: [],
        evidenceGaps: [],
    );
}

test('an empty retention review definition is consistent', function () {
    $resolved = app(ResolveRetentionReviews::class)->handle(
        new RetentionReviewDefinition(1, [], []),
        retentionCustody(),
        retentionPolicies(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedReviews)->toHaveCount(0);
});

test('a compliant retention review resolves against known custody and evidence', function () {
    $resolved = app(ResolveRetentionReviews::class)->handle(
        new RetentionReviewDefinition(1, [], [[
            'key' => 'review-001',
            'custody_key' => 'custody-001',
            'reviewer' => 'managing-partner',
            'reviewed_at' => '2026-08-18T12:00:00+08:00',
            'basis' => 'Retention policy remains appropriate.',
            'outcome' => 'compliant',
            'evidence_record_key' => 'evidence-001',
        ]]),
        retentionCustody(),
        retentionPolicies(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedReviews)->toHaveCount(1)
        ->and($resolved->resolvedReviews[0]['review_resolved'])->toBeTrue();
});

test('an exception-required review cannot resolve without an approved policy exception', function () {
    $resolved = app(ResolveRetentionReviews::class)->handle(
        new RetentionReviewDefinition(1, [], [[
            'key' => 'review-002',
            'custody_key' => 'custody-001',
            'reviewer' => 'managing-partner',
            'reviewed_at' => '2026-08-18T12:00:00+08:00',
            'basis' => 'Retention requires a temporary deviation.',
            'outcome' => 'exception_required',
            'policy_exception_key' => 'exception-missing',
            'evidence_record_key' => 'evidence-001',
        ]]),
        retentionCustody(),
        retentionPolicies(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent_with_gaps')
        ->and($resolved->resolvedReviews)->toHaveCount(0)
        ->and(array_column($resolved->exceptionGaps, 'code'))->toContain('retention_review_exception_not_approved');
});
