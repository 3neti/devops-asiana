<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Responsibility Coverage Console', function () {
    $this->get(route('responsibility-coverage.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect canonical Responsibility Coverage', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('responsibility-coverage.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ResponsibilityCoverage/Index')
            ->where('responsibilityCoverage.compiler_status', 'consistent_with_gaps')
            ->where('responsibilityCoverage.counts.requirements', 15)
            ->where('responsibilityCoverage.counts.covered', 9)
            ->where('responsibilityCoverage.counts.vacant', 2)
            ->where('responsibilityCoverage.counts.pending_activation', 4)
            ->where('responsibilityCoverage.counts.concentration_exposures', 1)
            ->has('responsibilityCoverage.reports.succession_gaps', 4)
            ->has('responsibilityCoverage.separation_constraints', 2)
            ->etc()
        );
});
