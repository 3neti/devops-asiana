<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\ClientAcceptance\ClientAcceptanceRepository;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\ClientMandates\ClientMandateRepository;
use App\ClientMandates\ResolveClientMandates;
use App\Engagements\EngagementRepository;
use App\Engagements\ResolveEngagements;
use App\FormationCompletion\FormationCompletionRepository;
use App\FormationCompletion\ResolveFormationCompletion;
use App\IdentityAndRoles\IdentityAndRoleRepository;
use App\IdentityAndRoles\ResolveIdentityAndRoles;
use App\MatterEvents\MatterEventRepository;
use App\MatterEvents\ResolveMatterEvents;
use App\Matters\MatterRepository;
use App\Matters\ResolveMatters;
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
use App\SuccessorAppointments\ResolveSuccessorAppointments;
use App\SuccessorAppointments\SuccessorAppointmentRepository;
use Inertia\Inertia;
use Inertia\Response;

class EngagementController extends Controller
{
    public function __invoke(
        AuthorityMatrixRepository $authorityMatrix,
        ClientMandateRepository $clientMandates,
        MatterRepository $matters,
        MatterEventRepository $matterEvents,
        EngagementRepository $engagements,
        ClientAcceptanceRepository $clientAcceptance,
        FormationCompletionRepository $formationCompletion,
        IdentityAndRoleRepository $identityAndRoles,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        RoleActivationRepository $roleActivations,
        RoleTransitionRepository $roleTransitions,
        SuccessorAppointmentRepository $successorAppointments,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveClientMandates $resolveClientMandates,
        ResolveMatters $resolveMatters,
        ResolveMatterEvents $resolveMatterEvents,
        ResolveEngagements $resolveEngagements,
        ResolveClientAcceptance $resolveClientAcceptance,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolveRoleActivations $resolveRoleActivations,
        ResolveRoleTransitions $resolveRoleTransitions,
        ResolveSuccessorAppointments $resolveSuccessorAppointments,
    ): Response {
        $policyRegistry = $policies->current();
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedPolicies = $resolvePolicyRegistry->handle($policyRegistry);
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $resolvedPolicies);
        $identityDefinition = $identityAndRoles->current();
        $resolvedRoleActivations = $resolveRoleActivations->handle($roleActivations->current(), $identityDefinition, $resolvedFormationCompletion);
        $resolvedRoleTransitions = $resolveRoleTransitions->handle($roleTransitions->current(), $identityDefinition);
        $resolvedSuccessorAppointments = $resolveSuccessorAppointments->handle($successorAppointments->current(), $identityDefinition, $resolvedPartnership, $resolvedRoleTransitions);
        $resolvedIdentities = $resolveIdentityAndRoles->handle($identityDefinition, $resolvedPartnership, $resolvedCoverage, formationCompletion: $resolvedFormationCompletion, roleActivations: $resolvedRoleActivations, roleTransitions: $resolvedRoleTransitions, successorAppointments: $resolvedSuccessorAppointments);
        $resolvedAuthority = $resolveAuthorityMatrix->handle($authorityMatrix->current(), $resolvedPartnership, $resolvedPolicies, $resolvedCoverage, $resolvedIdentities);
        $resolvedEngagements = $resolveEngagements->handle($engagements->current(), $resolveClientAcceptance->handle($clientAcceptance->current(), $policyRegistry), $resolvedPartnership, $resolvedPolicies);

        $resolvedMatters = $resolveMatters->handle($matters->current(), $resolvedEngagements);

        return Inertia::render('Engagements/Index', [
            'engagements' => $resolvedEngagements->toArray(),
            'clientMandates' => $resolveClientMandates->handle($clientMandates->current(), $resolvedEngagements, $resolvedAuthority)->toArray(),
            'matters' => $resolvedMatters->toArray(),
            'matterEvents' => $resolveMatterEvents->handle($matterEvents->current(), $resolvedMatters)->toArray(),
        ]);
    }
}
