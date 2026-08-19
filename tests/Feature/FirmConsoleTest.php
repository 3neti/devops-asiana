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
            ->where('partnershipAgreement.status', 'working_draft')
            ->where('partnershipAgreement.counts.decisions_required', 10)
            ->where('partnershipAgreement.counts.counsel_review', 9)
            ->where('partnershipAgreement.counts.conflicts', 0)
            ->where('partnershipAgreement.agreement.title', 'Partnership Agreement')
            ->where('partnershipAgreement.agreement.markdown', fn (string $markdown): bool => str_contains($markdown, 'professional accountability'))
            ->where('partnershipAgreement.agreement.markdown', fn (string $markdown): bool => str_contains($markdown, '[UNRESOLVED]'))
            ->where('formationCompletion.compiler_status', 'consistent_with_gaps')
            ->where('formationCompletion.firm_commenced', false)
            ->where('formationCompletion.counts.verified_commencements', 0)
            ->where('formationCompletion.counts.office_activation_bases', 0)
            ->has('formationCompletion.reports.formation_gaps', 3)
            ->has('formationCompletion.reports.legal_gaps', 1)
            ->has('formationCompletion.reports.capital_gaps', 2)
            ->has('formationCompletion.reports.counsel_review', 2)
        );
});
