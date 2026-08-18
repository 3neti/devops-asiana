<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Decision and Approval Console', function () {
    $this->get(route('decision-records.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the truthful empty Decision Record ledger', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('decision-records.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('DecisionRecords/Index')
            ->where('decisionRecords.compiler_status', 'consistent_with_gaps')
            ->where('decisionRecords.counts.decisions', 0)
            ->where('decisionRecords.counts.executable_decisions', 0)
            ->where('decisionRecords.counts.collective_admissions', 0)
            ->where('decisionRecords.counts.available_collective_candidates', 0)
            ->where('decisionRecords.counts.executions', 0)
            ->where('decisionRecords.counts.verifications', 0)
            ->has('decisionRecords.record_requirements', 8)
            ->has('decisionRecords.reports.readiness_gaps', 3)
            ->etc()
        );
});
