<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Identity and Role Console', function () {
    $this->get(route('identity-and-roles.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect canonical identities roles and assignments', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('identity-and-roles.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('IdentityAndRoles/Index')
            ->where('identityAndRoles.compiler_status', 'consistent_with_gaps')
            ->where('identityAndRoles.counts.identities', 2)
            ->where('identityAndRoles.counts.roles', 9)
            ->where('identityAndRoles.counts.assignments', 8)
            ->where('identityAndRoles.counts.admitted_activations', 0)
            ->where('identityAndRoles.counts.authority_effective', 0)
            ->where('identityAndRoles.counts.by_role_coverage.pending_activation', 7)
            ->where('identityAndRoles.counts.by_role_coverage.vacant', 2)
            ->has('identityAndRoles.reports.identity_gaps', 2)
            ->has('identityAndRoles.reports.activation_gaps', 1)
            ->where('roleActivations.compiler_status', 'consistent_with_gaps')
            ->where('roleActivations.counts.candidate_assignments', 8)
            ->where('roleActivations.counts.commencement_eligible_assignments', 0)
            ->where('roleActivations.counts.recorded_assumptions', 0)
            ->where('roleActivations.counts.admitted_activations', 0)
            ->where('roleActivations.counts.pending_assignments', 8)
            ->has('roleActivations.reports.activation_gaps', 9)
            ->etc()
        );
});
