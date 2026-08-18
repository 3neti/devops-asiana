<?php

namespace App\Http\Controllers;

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
use App\RoleActivations\ResolveRoleActivations;
use App\RoleActivations\RoleActivationRepository;
use Inertia\Inertia;
use Inertia\Response;

class IdentityAndRoleController extends Controller
{
    public function __invoke(
        IdentityAndRoleRepository $identityAndRoles,
        FormationCompletionRepository $formationCompletion,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        RoleActivationRepository $roleActivations,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveRoleActivations $resolveRoleActivations,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedResponsibilities = $resolveResponsibilityCoverage->handle(
            $responsibilityCoverage->current(),
            $resolvedPartnership,
            $resolvePolicyRegistry->handle($policies->current()),
        );

        $identityDefinition = $identityAndRoles->current();
        $resolvedRoleActivations = $resolveRoleActivations->handle(
            $roleActivations->current(),
            $identityDefinition,
            $resolvedFormationCompletion,
        );

        return Inertia::render('IdentityAndRoles/Index', [
            'roleActivations' => $resolvedRoleActivations->toArray(),
            'identityAndRoles' => $resolveIdentityAndRoles->handle(
                $identityDefinition,
                $resolvedPartnership,
                $resolvedResponsibilities,
                formationCompletion: $resolvedFormationCompletion,
                roleActivations: $resolvedRoleActivations,
            )->toArray(),
        ]);
    }
}
