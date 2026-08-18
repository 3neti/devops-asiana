<?php

use App\ControlHistory\InstitutionalControlHistoryVerificationLinkReconciliationDefinition;
use App\ControlHistory\ResolvedInstitutionalControlHistoryAnchorVerification;
use App\ControlHistory\ResolvedInstitutionalControlHistoryVerificationEvidenceLinks;
use App\ControlHistory\ResolveInstitutionalControlHistoryVerificationLinkReconciliations;

function reconciliationDefinition(array $records): InstitutionalControlHistoryVerificationLinkReconciliationDefinition
{
    return new InstitutionalControlHistoryVerificationLinkReconciliationDefinition(1, 'reconciliations-v1', 'links-v1', $records);
}

function linkProjection(array $links = []): ResolvedInstitutionalControlHistoryVerificationEvidenceLinks
{
    return new ResolvedInstitutionalControlHistoryVerificationEvidenceLinks(1, 'links-v1', 'verification-v1', $links, [], []);
}

function reconciliationVerification(): ResolvedInstitutionalControlHistoryAnchorVerification
{
    return new ResolvedInstitutionalControlHistoryAnchorVerification(1, 'verification-v1', 'history-integrity-v1', 'sha256', str_repeat('a', 64), str_repeat('b', 64), [], [], []);
}

test('it leaves the canonical empty reconciliation registry unresolved', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationLinkReconciliations)->handle(reconciliationDefinition([]), linkProjection(), reconciliationVerification())->toArray();

    expect($result['status'])->toBe('not_supplied')
        ->and($result['reconciliations'])->toBe([]);
});

test('it preserves an explicit matching reconciliation', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationLinkReconciliations)->handle(
        reconciliationDefinition([[
            'key' => 'reconciliation-001',
            'link_key' => 'link-001',
            'observed_verification_key' => 'verification-v1',
            'observed_verification_status' => 'verified',
            'observed_resolved_history_anchor' => str_repeat('b', 64),
            'observed_supplied_history_anchor' => str_repeat('a', 64),
            'reconciled' => true,
            'reconciled_by' => 'partner-001',
            'reconciled_at' => '2026-08-19T13:00:00+08:00',
            'basis' => 'Compared against the link registry.',
            'evidence_record_key' => 'evidence-003',
        ]]),
        linkProjection([[
            'key' => 'link-001',
            'verification_key' => 'verification-v1',
            'verification_status' => 'verified',
            'resolved_history_anchor' => str_repeat('b', 64),
            'supplied_history_anchor' => str_repeat('a', 64),
        ]]),
        reconciliationVerification(),
    )->toArray();

    expect($result['status'])->toBe('consistent')
        ->and($result['reconciliations'][0]['reconciled'])->toBeTrue()
        ->and($result['reports']['reconciliation_gaps'])->toBe([]);
});

test('it reports drift, unknown links, and outcome disagreement', function () {
    $result = (new ResolveInstitutionalControlHistoryVerificationLinkReconciliations)->handle(
        reconciliationDefinition([[
            'key' => 'reconciliation-002',
            'link_key' => 'missing-link',
            'observed_verification_key' => 'other-verification',
            'observed_verification_status' => 'mismatch_detected',
            'observed_resolved_history_anchor' => str_repeat('c', 64),
            'observed_supplied_history_anchor' => str_repeat('d', 64),
            'reconciled' => true,
            'reconciled_by' => 'partner-001',
            'reconciled_at' => 'invalid',
            'basis' => 'Drift review.',
            'evidence_record_key' => 'evidence-004',
        ]]),
        linkProjection(),
        reconciliationVerification(),
    )->toArray();
    $codes = array_column($result['reports']['reconciliation_gaps'], 'code');

    expect($result['status'])->toBe('consistent_with_gaps')
        ->and($codes)->toContain('reconciliation_without_link')
        ->and($codes)->toContain('invalid_reconciliation_time')
        ->and($codes)->toContain('reconciliation_snapshot_mismatch')
        ->and($codes)->toContain('reconciliation_outcome_mismatch');
});
