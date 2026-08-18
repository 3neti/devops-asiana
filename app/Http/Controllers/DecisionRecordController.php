<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\DecisionRecords\DecisionRecordRepository;
use App\DecisionRecords\ResolveDecisionRecords;
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
use App\RoleTransitions\ResolveRoleTransitions;
use App\RoleTransitions\RoleTransitionRepository;
use Inertia\Inertia;
use Inertia\Response;

class DecisionRecordController extends Controller
{
    public function __invoke(
        DecisionRecordRepository $decisionRecords,
        FormationCompletionRepository $formationCompletion,
        GovernanceMeetingRepository $governanceMeetings,
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        RoleActivationRepository $roleActivations,
        RoleTransitionRepository $roleTransitions,
        ResolveDecisionRecords $resolveDecisionRecords,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveRoleActivations $resolveRoleActivations,
        ResolveRoleTransitions $resolveRoleTransitions,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedPolicies = $resolvePolicyRegistry->handle($policies->current());
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $resolvedPolicies);
        $identityDefinition = $identityAndRoles->current();
        $resolvedRoleActivations = $resolveRoleActivations->handle($roleActivations->current(), $identityDefinition, $resolvedFormationCompletion);
        $resolvedRoleTransitions = $resolveRoleTransitions->handle($roleTransitions->current(), $identityDefinition);
        $resolvedIdentities = $resolveIdentityAndRoles->handle(
            $identityDefinition,
            $resolvedPartnership,
            $resolvedCoverage,
            formationCompletion: $resolvedFormationCompletion,
            roleActivations: $resolvedRoleActivations,
            roleTransitions: $resolvedRoleTransitions,
        );
        $resolvedAuthority = $resolveAuthorityMatrix->handle(
            $authorityMatrix->current(),
            $resolvedPartnership,
            $resolvedPolicies,
            $resolvedCoverage,
            $resolvedIdentities,
        );
        $resolvedGovernanceMeetings = $resolveGovernanceMeetings->handle(
            $governanceMeetings->current(),
            $resolvedPartnership,
            $resolvedPolicies,
            $resolvedAuthority,
        );

        return Inertia::render('DecisionRecords/Index', [
            'decisionRecords' => $resolveDecisionRecords->handle(
                $decisionRecords->current(),
                $resolvedPolicies,
                $resolvedAuthority,
                $resolvedGovernanceMeetings,
            )->toArray(),
        ]);
    }
}
