<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\GovernanceMeetings\GovernanceMeetingRepository;
use App\GovernanceMeetings\ResolveGovernanceMeetings;
use App\IdentityAndRoles\IdentityAndRoleRepository;
use App\IdentityAndRoles\ResolveIdentityAndRoles;
use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryRepository;
use App\Policies\ResolvePolicyRegistry;
use App\ResponsibilityCoverage\ResolveResponsibilityCoverage;
use App\ResponsibilityCoverage\ResponsibilityCoverageRepository;
use Inertia\Inertia;
use Inertia\Response;

class GovernanceMeetingController extends Controller
{
    public function __invoke(
        GovernanceMeetingRepository $governanceMeetings,
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedPolicies = $resolvePolicyRegistry->handle($policies->current());
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $resolvedPolicies);
        $resolvedIdentities = $resolveIdentityAndRoles->handle($identityAndRoles->current(), $resolvedPartnership, $resolvedCoverage);
        $resolvedAuthority = $resolveAuthorityMatrix->handle(
            $authorityMatrix->current(),
            $resolvedPartnership,
            $resolvedPolicies,
            $resolvedCoverage,
            $resolvedIdentities,
        );

        return Inertia::render('GovernanceMeetings/Index', [
            'governanceMeetings' => $resolveGovernanceMeetings->handle(
                $governanceMeetings->current(),
                $resolvedPartnership,
                $resolvedPolicies,
                $resolvedAuthority,
            )->toArray(),
        ]);
    }
}
