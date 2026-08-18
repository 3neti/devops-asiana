<?php

use App\ControlExports\ResolvedControlReviewEvidenceExport;
use App\ControlSignoffs\ControlReviewSignoffDefinition;
use App\ControlSignoffs\ResolveControlReviewSignoffs;

function cleanControlExport(): ResolvedControlReviewEvidenceExport
{
    return new ResolvedControlReviewEvidenceExport(
        schemaVersion: 1,
        exportKey: 'control-review-v1',
        source: 'institutional-control-review',
        payloadsExcluded: true,
        includedFields: ['key', 'status', 'gaps'],
        status: 'consistent',
        controls: [],
        conflicts: [],
    );
}

test('an empty sign-off definition is consistent', function () {
    $resolved = app(ResolveControlReviewSignoffs::class)->handle(
        new ControlReviewSignoffDefinition(1, [], []),
        cleanControlExport(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedSignoffs)->toHaveCount(0);
});

test('a sign-off resolves against the exact export snapshot and explicit boundary', function () {
    $resolved = app(ResolveControlReviewSignoffs::class)->handle(
        new ControlReviewSignoffDefinition(1, [], [[
            'key' => 'signoff-001',
            'export_key' => 'control-review-v1',
            'export_status' => 'consistent',
            'reviewer' => 'lester-hurtado',
            'reviewer_role' => 'Founding Partner',
            'reviewed_at' => '2026-08-18T14:00:00+08:00',
            'basis' => 'Reviewed the exported control summary and source provenance.',
            'outcome' => 'reviewed',
            'acknowledges_no_approval' => true,
            'evidence_record_key' => 'evidence-signoff-001',
        ]]),
        cleanControlExport(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedSignoffs)->toHaveCount(1)
        ->and($resolved->resolvedSignoffs[0]['signoff_resolved'])->toBeTrue();
});

test('a sign-off cannot suppress findings or imply approval', function () {
    $export = new ResolvedControlReviewEvidenceExport(
        schemaVersion: 1,
        exportKey: 'control-review-v1',
        source: 'institutional-control-review',
        payloadsExcluded: true,
        includedFields: ['key', 'status', 'gaps'],
        status: 'attention_required',
        controls: [],
        conflicts: [],
    );

    $resolved = app(ResolveControlReviewSignoffs::class)->handle(
        new ControlReviewSignoffDefinition(1, [], [[
            'key' => 'signoff-002',
            'export_key' => 'control-review-v1',
            'export_status' => 'attention_required',
            'reviewer' => 'lester-hurtado',
            'reviewer_role' => 'Founding Partner',
            'reviewed_at' => '2026-08-18T14:00:00+08:00',
            'basis' => 'Reviewed.',
            'outcome' => 'reviewed',
            'acknowledges_no_approval' => false,
        ]]),
        $export,
    );

    expect($resolved->toArray()['compiler_status'])->toBe('conflict_detected')
        ->and(array_column($resolved->conflicts, 'code'))->toContain('signoff_suppresses_findings')
        ->and(array_column($resolved->reviewGaps, 'code'))->toContain('signoff_approval_boundary_unacknowledged')
        ->and(array_column($resolved->evidenceGaps, 'code'))->toContain('missing_control_review_signoff_evidence');
});
