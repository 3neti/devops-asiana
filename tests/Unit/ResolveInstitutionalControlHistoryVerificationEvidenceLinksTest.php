<?php

use App\ControlHistory\InstitutionalControlHistoryVerificationEvidenceLinkDefinition;
use App\ControlHistory\ResolvedInstitutionalControlHistoryAnchorVerification;
use App\ControlHistory\ResolveInstitutionalControlHistoryVerificationEvidenceLinks;

function evidenceLinkDefinition(array $links): InstitutionalControlHistoryVerificationEvidenceLinkDefinition
{
    return new InstitutionalControlHistoryVerificationEvidenceLinkDefinition(1, 'links-v1', 'verification-v1', $links);
}

function verifiedAnchorProjection(string $status = 'verified'): ResolvedInstitutionalControlHistoryAnchorVerification
{
    return new ResolvedInstitutionalControlHistoryAnchorVerification(
        1,
        'verification-v1',
        'history-integrity-v1',
        'sha256',
        str_repeat('a', 64),
        str_repeat('b', 64),
        [],
        [],
        $status === 'verified' ? [] : [['code' => 'history_anchor_mismatch', 'message' => 'Mismatch.']],
    );
}

test('it leaves the canonical empty link registry unresolved', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationEvidenceLinks)->handle(evidenceLinkDefinition([]), verifiedAnchorProjection())->toArray();

    expect($result['status'])->toBe('not_supplied')
        ->and($result['links'])->toBe([]);
});

test('it admits a link only with an exact verification snapshot', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationEvidenceLinks)->handle(
        evidenceLinkDefinition([[
            'key' => 'link-001',
            'verification_key' => 'verification-v1',
            'verification_status' => 'verified',
            'resolved_history_anchor' => str_repeat('b', 64),
            'supplied_history_anchor' => str_repeat('a', 64),
            'artifact_key' => 'artifact-001',
            'evidence_record_key' => 'evidence-001',
            'linked_by' => 'partner-001',
            'linked_at' => '2026-08-19T12:00:00+08:00',
            'reason' => 'Independent anchor comparison artifact.',
            'payload' => 'never exported',
        ]]),
        verifiedAnchorProjection(),
    )->toArray();

    expect($result['status'])->toBe('consistent')
        ->and($result['links'][0])->not->toHaveKey('payload')
        ->and($result['reports']['link_gaps'])->toBe([]);
});

test('it reports snapshot drift and links to unverified comparisons', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationEvidenceLinks)->handle(
        evidenceLinkDefinition([[
            'key' => 'link-002',
            'verification_key' => 'other-verification',
            'verification_status' => 'verified',
            'resolved_history_anchor' => str_repeat('c', 64),
            'supplied_history_anchor' => str_repeat('d', 64),
            'artifact_key' => 'artifact-002',
            'evidence_record_key' => 'evidence-002',
            'linked_by' => 'partner-001',
            'linked_at' => 'invalid',
            'reason' => 'Mismatch artifact.',
        ]]),
        verifiedAnchorProjection('mismatch_detected'),
    )->toArray();
    $codes = array_column($result['reports']['link_gaps'], 'code');

    expect($result['status'])->toBe('consistent_with_gaps')
        ->and($codes)->toContain('verification_snapshot_key_mismatch')
        ->and($codes)->toContain('verification_snapshot_status_mismatch')
        ->and($codes)->toContain('verification_snapshot_anchor_mismatch')
        ->and($codes)->toContain('verification_snapshot_supplied_anchor_mismatch')
        ->and($codes)->toContain('invalid_link_time')
        ->and($codes)->toContain('link_to_unverified_comparison');
});
