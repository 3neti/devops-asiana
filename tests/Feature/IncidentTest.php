<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Incident Console', function () {
    $this->get(route('incidents.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Incident accountability gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('incidents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Incidents/Index')
            ->where('incidents.compiler_status', 'consistent_with_gaps')
            ->where('incidents.counts.incident_records', 0)
            ->where('incidents.counts.active_response', 0)
            ->has('incidents.record_requirements', 15)
            ->has('incidents.governing_policies', 4)
            ->has('incidents.reports.readiness_gaps', 2)
            ->etc()
        );
});
