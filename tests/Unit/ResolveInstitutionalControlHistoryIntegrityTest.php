<?php

use App\ControlHistory\InstitutionalControlHistoryIntegrityDefinition;
use App\ControlHistory\ResolvedInstitutionalControlHistory;
use App\ControlHistory\ResolveInstitutionalControlHistoryIntegrity;

function integrityDefinition(array $ordering = ['occurred_at', 'event_kind', 'event_key']): InstitutionalControlHistoryIntegrityDefinition
{
    return new InstitutionalControlHistoryIntegrityDefinition(1, 'history-integrity-v1', 'control-review-lifecycle', 'sha256', $ordering, false);
}

function resolvedHistory(array $events, array $gaps = []): ResolvedInstitutionalControlHistory
{
    return new ResolvedInstitutionalControlHistory(1, 'history-v1', 'control-review-lifecycle', true, ['eligibility_review', 'closure_decision'], $events, [], $gaps);
}

test('it creates deterministic payload-free event and history anchors', function () {
    $history = resolvedHistory([
        [
            'event_key' => 'eligibility:001',
            'event_kind' => 'eligibility_review',
            'source_reference' => 'eligibility-001',
            'occurred_at' => '2026-08-19T09:00:00+08:00',
            'actor' => 'partner-001',
            'state' => 'eligible',
            'payload' => 'must-not-be-anchored',
        ],
    ]);

    $resolved = (new ResolveInstitutionalControlHistoryIntegrity)->handle(integrityDefinition(), $history);
    $result = $resolved->toArray();

    expect($result['status'])->toBe('consistent')
        ->and($result['payloads_excluded'])->toBeTrue()
        ->and($result['history_anchor'])->toHaveLength(64)
        ->and($result['event_anchors'][0])->not->toHaveKey('payload')
        ->and($result['event_anchors'][0]['anchor'])->toHaveLength(64);
});

test('it reports unstable ordering, duplicate keys, and source gaps', function () {
    $history = resolvedHistory([
        [
            'event_key' => 'decision:001',
            'event_kind' => 'closure_decision',
            'source_reference' => 'decision-001',
            'occurred_at' => '2026-08-19T10:00:00+08:00',
            'actor' => 'partner-001',
            'state' => 'closed',
        ],
        [
            'event_key' => 'decision:001',
            'event_kind' => 'closure_decision',
            'source_reference' => 'decision-001',
            'occurred_at' => '2026-08-19T09:00:00+08:00',
            'actor' => 'partner-001',
            'state' => 'closed',
        ],
    ], [['code' => 'history_event_actor_missing', 'message' => 'Source actor missing.']]);

    $result = (new ResolveInstitutionalControlHistoryIntegrity)->handle(integrityDefinition(), $history)->toArray();
    $codes = array_column($result['reports']['integrity_gaps'], 'code');

    expect($result['status'])->toBe('consistent_with_gaps')
        ->and($codes)->toContain('source_history_gap')
        ->and($codes)->toContain('history_ordering_mismatch')
        ->and($codes)->toContain('duplicate_history_event_key');
});

test('it rejects payload export and unsupported ordering', function () {
    $definition = new InstitutionalControlHistoryIntegrityDefinition(1, 'history-integrity-v1', 'other-source', 'md5', ['event_key'], true);
    $result = (new ResolveInstitutionalControlHistoryIntegrity)->handle($definition, resolvedHistory([]))->toArray();
    $codes = array_column($result['reports']['conflicts'], 'code');

    expect($result['status'])->toBe('conflict_detected')
        ->and($codes)->toContain('unsupported_anchor_algorithm')
        ->and($codes)->toContain('payload_anchor_forbidden')
        ->and($codes)->toContain('history_source_mismatch')
        ->and($codes)->toContain('unsupported_history_ordering');
});
