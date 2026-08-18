<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\DecisionRecords\DecisionRecordRepository;
use App\DecisionRecords\ResolveDecisionRecords;
use App\FormationBootstrap\FormationBootstrapRepository;
use App\FormationBootstrap\ResolveFormationBootstrap;
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

class PolicyRegistryController extends Controller
{
    public function __invoke(
        PolicyRegistryRepository $registries,
        FormationBootstrapRepository $formationBootstrap,
        FormationCompletionRepository $formationCompletion,
        DecisionRecordRepository $decisionRecords,
        GovernanceMeetingRepository $governanceMeetings,
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        RoleActivationRepository $roleActivations,
        RoleTransitionRepository $roleTransitions,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveFormationBootstrap $resolveFormationBootstrap,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveDecisionRecords $resolveDecisionRecords,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolveRoleActivations $resolveRoleActivations,
        ResolveRoleTransitions $resolveRoleTransitions,
    ): Response {
        $policyDefinition = $registries->current();
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedFormationBootstrap = $resolveFormationBootstrap->handle(
            $formationBootstrap->current(),
            $resolvedPartnership,
            $policyDefinition,
        );
        $basePolicies = $resolvePolicyRegistry->handle(
            $policyDefinition,
            formationBootstrap: $resolvedFormationBootstrap,
        );
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $basePolicies);
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
        $resolvedAuthority = $resolveAuthorityMatrix->handle($authorityMatrix->current(), $resolvedPartnership, $basePolicies, $resolvedCoverage, $resolvedIdentities);
        $resolvedGovernanceMeetings = $resolveGovernanceMeetings->handle($governanceMeetings->current(), $resolvedPartnership, $basePolicies, $resolvedAuthority);
        $resolvedDecisionRecords = $resolveDecisionRecords->handle($decisionRecords->current(), $basePolicies, $resolvedAuthority, $resolvedGovernanceMeetings);

        return Inertia::render('Policies/Index', [
            'formationBootstrap' => $resolvedFormationBootstrap->toArray(),
            'registry' => $resolvePolicyRegistry->handle(
                $policyDefinition,
                decisionRecords: $resolvedDecisionRecords,
                formationBootstrap: $resolvedFormationBootstrap,
            )->toArray(),
        ]);
    }
}
