<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Break-glass Access Console', function () {
    $this->get(route('break-glass-access.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Break-glass Access gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('break-glass-access.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('BreakGlassAccess/Index')
            ->where('breakGlassAccess.compiler_status', 'consistent_with_gaps')
            ->where('breakGlassAccess.counts.access_records', 0)
            ->where('breakGlassAccess.counts.active_emergency_authority', 0)
            ->has('breakGlassAccess.record_requirements', 15)
            ->has('breakGlassAccess.governing_policies', 4)
            ->has('breakGlassAccess.reports.readiness_gaps', 4)
            ->etc()
        );
});
