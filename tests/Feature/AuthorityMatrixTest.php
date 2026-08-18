<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Authority Matrix Console', function () {
    $this->get(route('authority-matrix.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Authority Matrix', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('authority-matrix.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AuthorityMatrix/Index')
            ->where('authorityMatrix.compiler_status', 'consistent_with_gaps')
            ->where('authorityMatrix.counts.domains', 7)
            ->where('authorityMatrix.counts.actions', 7)
            ->where('authorityMatrix.counts.entries', 7)
            ->where('authorityMatrix.counts.deferred_decisions', 7)
            ->where('authorityMatrix.counts.effective_entries', 0)
            ->where('authorityMatrix.counts.by_resolution.design_only', 4)
            ->where('authorityMatrix.counts.by_resolution.pending_activation', 2)
            ->where('authorityMatrix.counts.by_resolution.vacant_holder', 1)
            ->has('authorityMatrix.reports.holder_gaps', 1)
            ->etc()
        );
});
