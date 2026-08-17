<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Production Access Console', function () {
    $this->get(route('production-access.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Production Access gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('production-access.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ProductionAccess/Index')
            ->where('productionAccess.compiler_status', 'consistent_with_gaps')
            ->where('productionAccess.counts.access_grants', 0)
            ->where('productionAccess.counts.active_authority', 0)
            ->has('productionAccess.grant_requirements', 13)
            ->has('productionAccess.governing_policies', 3)
            ->has('productionAccess.reports.readiness_gaps', 3)
            ->etc()
        );
});
