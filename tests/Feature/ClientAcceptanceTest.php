<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot inspect Client Acceptance', function () {
    $this->get(route('client-acceptance.index'))->assertRedirect(route('login'));
});

test('authenticated readers can inspect acceptance readiness and review standards', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('client-acceptance.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('ClientAcceptance/Index')
            ->where('acceptance.compiler_status', 'consistent_with_gaps')
            ->where('acceptance.governing_policy.title', 'Client Acceptance Policy')
            ->where('acceptance.governing_policy.status', 'draft')
            ->where('acceptance.governing_policy.operative', false)
            ->where('acceptance.counts.prospective_clients', 0)
            ->has('acceptance.required_assessments', 16)
            ->has('acceptance.reports.readiness_gaps', 1)
        );
});
