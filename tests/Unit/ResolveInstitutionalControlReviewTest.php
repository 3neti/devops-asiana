<?php

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\EvidenceCustody\ResolvedEvidenceCustody;
use App\InstitutionalControls\InstitutionalControlReviewDefinition;
use App\InstitutionalControls\ResolveInstitutionalControlReview;
use App\RetentionFindings\ResolvedRetentionFindingLinks;
use App\RetentionReviews\ResolvedRetentionReviews;

function cleanCustody(): ResolvedEvidenceCustody
{
    return new ResolvedEvidenceCustody(1, [], [], [], [], [], [], [], [], []);
}

function cleanRetentionReviews(): ResolvedRetentionReviews
{
    return new ResolvedRetentionReviews(1, [], [], [], [], [], [], []);
}

function cleanRetentionFindings(): ResolvedRetentionFindingLinks
{
    return new ResolvedRetentionFindingLinks(1, [], [], [], [], [], [], []);
}

function cleanCorrectiveActions(): ResolvedCorrectiveActions
{
    return new ResolvedCorrectiveActions(1, [], [], [], [], [], [], [], [], []);
}

test('an empty institutional control review is consistent', function () {
    $resolved = app(ResolveInstitutionalControlReview::class)->handle(
        new InstitutionalControlReviewDefinition(1, []),
        cleanCustody(),
        cleanRetentionReviews(),
        cleanRetentionFindings(),
        cleanCorrectiveActions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->controlReviews)->toHaveCount(0);
});

test('the review summarizes clean source compilers without changing them', function () {
    $resolved = app(ResolveInstitutionalControlReview::class)->handle(
        new InstitutionalControlReviewDefinition(1, [[
            'key' => 'custody',
            'label' => 'Evidence Custody',
            'source' => 'evidence-custody',
            'question' => 'Are custody facts complete?',
        ]]),
        cleanCustody(),
        cleanRetentionReviews(),
        cleanRetentionFindings(),
        cleanCorrectiveActions(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->controlReviews[0]['status'])->toBe('consistent')
        ->and($resolved->controlReviews[0]['gap_count'])->toBe(0);
});

test('source gaps become attention findings while remaining attributed to their source', function () {
    $custody = new ResolvedEvidenceCustody(
        schemaVersion: 1,
        requirements: [],
        records: [],
        resolvedRecords: [],
        conflicts: [],
        sourceGaps: [['code' => 'custody_source_incomplete', 'message' => 'Source is incomplete.']],
        custodyGaps: [],
        retentionGaps: [],
        integrityGaps: [],
        dispositionGaps: [],
    );

    $resolved = app(ResolveInstitutionalControlReview::class)->handle(
        new InstitutionalControlReviewDefinition(1, [[
            'key' => 'custody',
            'label' => 'Evidence Custody',
            'source' => 'evidence-custody',
            'question' => 'Are custody facts complete?',
        ]]),
        $custody,
        cleanRetentionReviews(),
        cleanRetentionFindings(),
        cleanCorrectiveActions(),
    );

    expect($resolved->toArray()['compiler_status'])->toBe('attention_required')
        ->and($resolved->controlReviews[0]['gaps'][0])->toMatchArray([
            'category' => 'source_gaps',
            'code' => 'custody_source_incomplete',
        ]);
});
