<?php

use App\ControlClosures\ResolvedControlReviewClosureEligibility;
use App\ControlDecisions\ResolvedControlReviewClosureDecisions;
use App\ControlHistory\InstitutionalControlHistoryDefinition;
use App\ControlHistory\ResolveInstitutionalControlHistory;
use App\ControlReconciliation\ResolvedControlReviewClosureReconciliations;

function historyDefinition(array $eventKinds = ['eligibility_review', 'closure_decision', 'closure_reconciliation']): InstitutionalControlHistoryDefinition
{
    return new InstitutionalControlHistoryDefinition(1, 'history-v1', 'control-review-lifecycle', false, $eventKinds);
}

test('it projects an append-only chronological history without payloads', function () {
    $resolved = (new ResolveInstitutionalControlHistory)->handle(
        historyDefinition(),
        new ResolvedControlReviewClosureEligibility(1, [], [], [[
            'key' => 'eligibility-001',
            'reviewed_at' => '2026-08-19T09:00:00+08:00',
            'reviewed_by' => 'partner-001',
            'closure_eligible' => true,
        ]], [], [], []),
        new ResolvedControlReviewClosureDecisions(1, [], [], [[
            'key' => 'decision-001',
            'decided_at' => '2026-08-19T10:00:00+08:00',
            'decided_by' => 'partner-001',
            'decision' => 'closed',
        ]], [], [], []),
        new ResolvedControlReviewClosureReconciliations(1, [], [], [[
            'key' => 'reconciliation-001',
            'reconciled_at' => '2026-08-19T11:00:00+08:00',
            'reconciled_by' => 'partner-001',
            'reconciled' => true,
        ]], [], [], []),
    );

    expect($resolved->toArray())
        ->status->toBe('consistent')
        ->payloads_excluded->toBeTrue()
        ->events->toHaveCount(3)
        ->events->sequence(
            fn ($event) => $event->event_kind->toBe('eligibility_review'),
            fn ($event) => $event->event_kind->toBe('closure_decision'),
            fn ($event) => $event->event_kind->toBe('closure_reconciliation'),
        );
});

test('it reports unsupported event kinds and incomplete chronology', function () {
    $resolved = (new ResolveInstitutionalControlHistory)->handle(
        historyDefinition(['closure_decision']),
        new ResolvedControlReviewClosureEligibility(1, [], [], [[
            'key' => 'eligibility-002',
            'closure_eligible' => false,
        ]], [], [], []),
        new ResolvedControlReviewClosureDecisions(1, [], [], [], [], [], []),
        new ResolvedControlReviewClosureReconciliations(1, [], [], [], [], [], []),
    );

    $result = $resolved->toArray();
    $codes = array_column($result['reports']['history_gaps'], 'code');

    expect($result['status'])->toBe('consistent_with_gaps')
        ->and($codes)->toContain('unsupported_history_event_kind')
        ->and($codes)->toContain('history_event_time_missing')
        ->and($codes)->toContain('history_event_actor_missing');
});
