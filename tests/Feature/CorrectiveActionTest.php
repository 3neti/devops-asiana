<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Corrective Action Console', function () {
    $this->get(route('corrective-actions.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Corrective Action Register', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('corrective-actions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('CorrectiveActions/Index')
            ->where('correctiveActions.compiler_status', 'consistent_with_gaps')
            ->where('correctiveActions.counts.corrective_actions', 0)
            ->where('correctiveActions.counts.overdue', 0)
            ->where('correctiveActions.counts.ready_for_closure', 0)
            ->has('correctiveActions.record_requirements', 13)
            ->has('correctiveActions.governing_policies', 5)
            ->has('correctiveActions.reports.readiness_gaps', 1)
            ->etc()
        );
});
