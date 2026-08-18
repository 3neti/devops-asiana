<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\FormationCompletion\FormationCompletionRepository;
use App\FormationCompletion\ResolveFormationCompletion;
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

class AuthorityMatrixController extends Controller
{
    public function __invoke(
        AuthorityMatrixRepository $authorityMatrix,
        FormationCompletionRepository $formationCompletion,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedPolicies = $resolvePolicyRegistry->handle($policies->current());
        $resolvedCoverage = $resolveResponsibilityCoverage->handle(
            $responsibilityCoverage->current(),
            $resolvedPartnership,
            $resolvedPolicies,
        );
        $resolvedIdentities = $resolveIdentityAndRoles->handle(
            $identityAndRoles->current(),
            $resolvedPartnership,
            $resolvedCoverage,
            formationCompletion: $resolvedFormationCompletion,
        );

        return Inertia::render('AuthorityMatrix/Index', [
            'authorityMatrix' => $resolveAuthorityMatrix->handle(
                $authorityMatrix->current(),
                $resolvedPartnership,
                $resolvedPolicies,
                $resolvedCoverage,
                $resolvedIdentities,
            )->toArray(),
        ]);
    }
}
