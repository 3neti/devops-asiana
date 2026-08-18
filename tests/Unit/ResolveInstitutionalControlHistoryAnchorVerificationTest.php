<?php

use App\ControlHistory\InstitutionalControlHistoryAnchorVerificationDefinition;
use App\ControlHistory\ResolvedInstitutionalControlHistoryIntegrity;
use App\ControlHistory\ResolveInstitutionalControlHistoryAnchorVerification;

function anchorVerificationDefinition(?string $historyAnchor, array $eventAnchors = []): InstitutionalControlHistoryAnchorVerificationDefinition
{
    return new InstitutionalControlHistoryAnchorVerificationDefinition(
        1,
        'verification-v1',
        'history-integrity-v1',
        'sha256',
        $historyAnchor,
        $eventAnchors,
    );
}

function integrityProjection(): ResolvedInstitutionalControlHistoryIntegrity
{
    return new ResolvedInstitutionalControlHistoryIntegrity(
        1,
        'history-integrity-v1',
        'control-review-lifecycle',
        'sha256',
        ['occurred_at', 'event_kind', 'event_key'],
        true,
        str_repeat('a', 64),
        [['event_key' => 'decision:001', 'anchor' => str_repeat('b', 64)]],
        [],
        [],
    );
}

test('it leaves verification pending when no supplied anchor exists', function () {
    $result = (new ResolveInstitutionalControlHistoryAnchorVerification)->handle(anchorVerificationDefinition(null), integrityProjection())->toArray();
    $codes = array_column($result['reports']['verification_gaps'], 'code');

    expect($result['status'])->toBe('not_supplied')
        ->and($codes)->toContain('history_anchor_not_supplied')
        ->and($codes)->toContain('event_anchor_not_supplied');
});

test('it verifies matching history and event anchors', function () {
    $result = (new ResolveInstitutionalControlHistoryAnchorVerification)->handle(
        anchorVerificationDefinition(str_repeat('a', 64), [['event_key' => 'decision:001', 'anchor' => str_repeat('b', 64)]]),
        integrityProjection(),
    )->toArray();

    expect($result['status'])->toBe('verified')
        ->and($result['event_comparisons'][0]['status'])->toBe('matched')
        ->and($result['reports']['verification_gaps'])->toBe([]);
});

test('it exposes mismatches and unexpected supplied anchors', function () {
    $result = (new ResolveInstitutionalControlHistoryAnchorVerification)->handle(
        anchorVerificationDefinition(str_repeat('c', 64), [
            ['event_key' => 'decision:001', 'anchor' => str_repeat('d', 64)],
            ['event_key' => 'unexpected:001', 'anchor' => str_repeat('e', 64)],
        ]),
        integrityProjection(),
    )->toArray();
    $codes = array_column($result['reports']['verification_gaps'], 'code');

    expect($result['status'])->toBe('mismatch_detected')
        ->and($codes)->toContain('history_anchor_mismatch')
        ->and($codes)->toContain('event_anchor_mismatch')
        ->and($codes)->toContain('unexpected_supplied_event_anchor');
});
