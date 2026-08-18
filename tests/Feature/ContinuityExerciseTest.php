<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the Continuity Exercise Console', function () {
    $this->get(route('continuity-exercises.index'))->assertRedirect(route('login'));
});

test('authenticated users can inspect the canonical Continuity Exercise Register', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('continuity-exercises.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ContinuityExercises/Index')
            ->where('continuityExercises.compiler_status', 'consistent_with_gaps')
            ->where('continuityExercises.counts.exercise_records', 0)
            ->where('continuityExercises.counts.objectives_missed', 0)
            ->where('continuityExercises.counts.unresolved_gaps', 0)
            ->has('continuityExercises.record_requirements', 13)
            ->has('continuityExercises.governing_policies', 3)
            ->has('continuityExercises.reports.readiness_gaps', 3)
            ->etc()
        );
});
