<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Engagement Console', function () {
    $this->get(route('engagements.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Engagement Opening gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('engagements.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Engagements/Index')
            ->where('engagements.compiler_status', 'consistent_with_gaps')
            ->where('engagements.counts.engagements', 0)
            ->where('engagements.counts.open_for_client_work', 0)
            ->has('engagements.opening_requirements', 10)
            ->has('engagements.governing_policies', 2)
            ->has('engagements.reports.readiness_gaps', 2)
            ->where('clientMandates.compiler_status', 'consistent')
            ->where('clientMandates.counts.action_requests', 0)
            ->where('clientMandates.counts.permitted_actions', 0)
            ->etc()
        );
});
