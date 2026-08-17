<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Change Console', function () {
    $this->get(route('changes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Change execution gate', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('changes.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Changes/Index')
            ->where('changes.compiler_status', 'consistent_with_gaps')
            ->where('changes.counts.change_records', 0)
            ->where('changes.counts.executable_authority', 0)
            ->has('changes.record_requirements', 15)
            ->has('changes.governing_policies', 3)
            ->has('changes.reports.readiness_gaps', 3)
            ->etc()
        );
});
