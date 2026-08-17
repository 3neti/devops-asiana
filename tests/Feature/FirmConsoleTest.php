<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot inspect the Firm Console', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('partners can inspect the resolved founding state', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('FirmConsole/Index')
            ->where('partnership.formation.firm.name', 'DevOps Asiana')
            ->where('partnership.projections.partnership.0.name', 'Lester B. Hurtado')
            ->where('partnership.projections.partnership.1.name', 'Angelica Anaïs C. Santos')
            ->where('partnership.projections.management.0.holder_name', 'Angelica Anaïs C. Santos')
            ->where('partnership.projections.economics.basis', 'Engagement Contribution')
            ->where('partnership.projections.economics.firm_allocation.percentage', 20)
            ->has('partnership.reports.decision_gaps', 6)
            ->has('partnership.reports.responsibility_gaps', 2)
        );
});
