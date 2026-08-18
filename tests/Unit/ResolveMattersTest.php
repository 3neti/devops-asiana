<?php

use App\Engagements\ResolvedEngagements;
use App\Matters\MatterDefinition;
use App\Matters\ResolveMatters;

function emptyMatterEngagements(): ResolvedEngagements
{
    return new ResolvedEngagements(1, [], [], [], [], [], [], [], [], []);
}

test('an empty Matter register is consistent without inventing professional work', function () {
    $resolved = (new ResolveMatters)->handle(new MatterDefinition(1, [], [], []), emptyMatterEngagements())->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['matters'])->toBe(0)
        ->and($resolved['counts']['active_matters'])->toBe(0);
});

test('a Matter cannot become accountable without an open Engagement and singular Responsible Partner', function () {
    $resolved = (new ResolveMatters)->handle(
        new MatterDefinition(1, [], [[
            'key' => 'production-migration',
            'lifecycle_status' => 'active',
            'engagement_key' => 'managed-cloud-operations',
            'responsible_partner_key' => 'lester-hurtado',
        ]], []),
        emptyMatterEngagements(),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['matters'][0]['may_perform_matter_work'])->toBeFalse()
        ->and(array_column($resolved['reports']['engagement_gaps'], 'code'))
        ->toContain('matter_engagement_missing')
        ->and(array_column($resolved['reports']['responsibility_gaps'], 'code'))
        ->toContain('matter_responsible_partner_mismatch')
        ->and(array_column($resolved['reports']['scope_gaps'], 'code'))
        ->toContain('matter_scope_incomplete');
});
