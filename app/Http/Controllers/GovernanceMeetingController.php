<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\FormationCompletion\FormationCompletionRepository;
use App\FormationCompletion\ResolveFormationCompletion;
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
use App\RoleActivations\ResolveRoleActivations;
use App\RoleActivations\RoleActivationRepository;
use Inertia\Inertia;
use Inertia\Response;

class GovernanceMeetingController extends Controller
{
    public function __invoke(
        GovernanceMeetingRepository $governanceMeetings,
        FormationCompletionRepository $formationCompletion,
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        RoleActivationRepository $roleActivations,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveRoleActivations $resolveRoleActivations,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedPolicies = $resolvePolicyRegistry->handle($policies->current());
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $resolvedPolicies);
        $identityDefinition = $identityAndRoles->current();
        $resolvedRoleActivations = $resolveRoleActivations->handle($roleActivations->current(), $identityDefinition, $resolvedFormationCompletion);
        $resolvedIdentities = $resolveIdentityAndRoles->handle(
            $identityDefinition,
            $resolvedPartnership,
            $resolvedCoverage,
            formationCompletion: $resolvedFormationCompletion,
            roleActivations: $resolvedRoleActivations,
        );
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
