<?php

use App\ControlExports\ControlReviewEvidenceExportDefinition;
use App\ControlExports\ResolveControlReviewEvidenceExport;
use App\InstitutionalControls\ResolvedInstitutionalControlReview;

function cleanControlReview(): ResolvedInstitutionalControlReview
{
    return new ResolvedInstitutionalControlReview(
        schemaVersion: 1,
        controls: [],
        controlReviews: [],
        conflicts: [],
    );
}

test('an empty control review exports consistently without payloads', function () {
    $resolved = app(ResolveControlReviewEvidenceExport::class)->handle(
        new ControlReviewEvidenceExportDefinition(1, 'control-review-v1', 'institutional-control-review', true, false, ['key', 'status', 'gaps']),
        cleanControlReview(),
    );

    expect($resolved->toArray())
        ->status->toBe('consistent')
        ->and($resolved->payloadsExcluded)->toBeTrue()
        ->and($resolved->controls)->toHaveCount(0);
});

test('the export preserves control gap provenance and selected fields', function () {
    $review = new ResolvedInstitutionalControlReview(
        schemaVersion: 1,
        controls: [],
        controlReviews: [[
            'key' => 'evidence-custody',
            'label' => 'Evidence Custody',
            'source' => 'evidence-custody',
            'question' => 'Are custody facts complete?',
            'status' => 'attention_required',
            'gap_count' => 1,
            'gaps' => [[
                'category' => 'source_gaps',
                'code' => 'custody_source_incomplete',
                'message' => 'Source is incomplete.',
            ]],
        ]],
        conflicts: [],
    );

    $resolved = app(ResolveControlReviewEvidenceExport::class)->handle(
        new ControlReviewEvidenceExportDefinition(1, 'control-review-v1', 'institutional-control-review', true, false, ['key', 'status', 'gap_count', 'gaps']),
        $review,
    );

    expect($resolved->toArray())
        ->status->toBe('attention_required')
        ->and($resolved->controls[0])->toMatchArray([
            'key' => 'evidence-custody',
            'status' => 'attention_required',
            'gap_count' => 1,
        ])
        ->and($resolved->controls[0]['gaps'][0]['category'])->toBe('source_gaps');
});

test('payload inclusion and unsupported fields are conflicts', function () {
    $resolved = app(ResolveControlReviewEvidenceExport::class)->handle(
        new ControlReviewEvidenceExportDefinition(1, 'control-review-v1', 'wrong-source', true, true, ['key', 'secret_payload']),
        cleanControlReview(),
    );

    expect($resolved->toArray()['status'])->toBe('conflict_detected')
        ->and(array_column($resolved->conflicts, 'code'))->toContain('invalid_export_source', 'payload_export_forbidden', 'invalid_export_field');
});
