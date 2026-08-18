<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\DecisionRecords\DecisionRecordRepository;
use App\DecisionRecords\ResolveDecisionRecords;
use App\FormationBootstrap\FormationBootstrapRepository;
use App\FormationBootstrap\ResolveFormationBootstrap;
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

class PolicyRegistryController extends Controller
{
    public function __invoke(
        PolicyRegistryRepository $registries,
        FormationBootstrapRepository $formationBootstrap,
        DecisionRecordRepository $decisionRecords,
        GovernanceMeetingRepository $governanceMeetings,
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
        ResolveFormationBootstrap $resolveFormationBootstrap,
        ResolveDecisionRecords $resolveDecisionRecords,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
    ): Response {
        $policyDefinition = $registries->current();
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
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
        $resolvedIdentities = $resolveIdentityAndRoles->handle($identityAndRoles->current(), $resolvedPartnership, $resolvedCoverage);
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
