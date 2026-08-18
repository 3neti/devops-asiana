<?php

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\ClientMandates\ClientMandateDefinition;
use App\ClientMandates\ResolveClientMandates;
use App\Engagements\ResolvedEngagements;

function emptyClientEngagements(): ResolvedEngagements
{
    return new ResolvedEngagements(1, [], [], [], [], [], [], [], [], []);
}

function emptyClientAuthority(): ResolvedAuthorityMatrix
{
    return new ResolvedAuthorityMatrix(1, [], [], [], [], [], [], [], [], [], [], [], []);
}

test('an empty client mandate register is consistent without inventing action permission', function () {
    $resolved = (new ResolveClientMandates)->handle(
        new ClientMandateDefinition(1, [], [], []),
        emptyClientEngagements(),
        emptyClientAuthority(),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['action_requests'])->toBe(0)
        ->and($resolved['counts']['permitted_actions'])->toBe(0);
});

test('an action cannot be permitted without every independent gate', function () {
    $resolved = (new ResolveClientMandates)->handle(
        new ClientMandateDefinition(1, [], [[
            'key' => 'release-001',
            'engagement_key' => 'managed-cloud-operations',
            'actor_identity_key' => 'angelica-santos',
            'action_key' => 'deploy-release',
            'environment' => 'production',
            'system' => 'payments-platform',
        ]], []),
        emptyClientEngagements(),
        emptyClientAuthority(),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['action_requests'][0]['permitted'])->toBeFalse()
        ->and($resolved['permitted_actions'])->toBeEmpty()
        ->and(array_column($resolved['reports']['mandate_gaps'], 'code'))
        ->toContain('engagement_not_open_for_action')
        ->and(array_column($resolved['reports']['authority_gaps'], 'code'))
        ->toContain('firm_authority_does_not_cover_action')
        ->and(array_column($resolved['reports']['approval_gaps'], 'code'))
        ->toContain('specific_approval_missing');
});
