<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Governance Meeting Console', function () {
    $this->get(route('governance-meetings.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect unresolved governance mechanics without fabricated meetings', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('governance-meetings.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('GovernanceMeetings/Index')
            ->where('governanceMeetings.compiler_status', 'consistent_with_gaps')
            ->where('governanceMeetings.counts.governing_partners', 2)
            ->where('governanceMeetings.counts.governance_weight', 100)
            ->where('governanceMeetings.counts.reserved_matters', 11)
            ->where('governanceMeetings.counts.meetings', 0)
            ->where('governanceMeetings.counts.decision_record_candidates', 0)
            ->has('governanceMeetings.meeting_requirements', 9)
            ->has('governanceMeetings.reports.readiness_gaps', 8)
            ->etc()
        );
});
