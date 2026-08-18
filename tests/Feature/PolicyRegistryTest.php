<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot inspect the policy register', function () {
    $this->get(route('policy-registry.index'))->assertRedirect(route('login'));
});

test('authenticated readers can inspect policy lifecycle truth', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('policy-registry.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Policies/Index')
            ->where('registry.compiler_status', 'consistent')
            ->where('registry.counts.policies', 12)
            ->where('registry.counts.approval_admissions', 0)
            ->where('registry.counts.publications', 0)
            ->where('registry.counts.activations', 0)
            ->where('registry.counts.available_decision_candidates', 0)
            ->where('registry.counts.by_status.draft', 12)
            ->where('registry.counts.by_status.effective', 0)
            ->where('registry.counts.exceptions', 0)
            ->where('registry.policies.0.title', 'Partnership Governance Policy')
            ->where('registry.policies.0.current.content_integrity', 'mutable_draft')
            ->has('registry.reports.conflicts', 0)
        );
});
