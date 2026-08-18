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
        ResolveDecisionRecords $resolveDecisionRecords,
        ResolveFormationCompletion $resolveFormationCompletion,
        ResolveGovernanceMeetings $resolveGovernanceMeetings,
        ResolveAuthorityMatrix $resolveAuthorityMatrix,
        ResolveIdentityAndRoles $resolveIdentityAndRoles,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($partnership->current());
        $resolvedFormationCompletion = $resolveFormationCompletion->handle($formationCompletion->current(), $resolvedPartnership);
        $resolvedPolicies = $resolvePolicyRegistry->handle($policies->current());
        $resolvedCoverage = $resolveResponsibilityCoverage->handle($responsibilityCoverage->current(), $resolvedPartnership, $resolvedPolicies);
        $resolvedIdentities = $resolveIdentityAndRoles->handle(
            $identityAndRoles->current(),
            $resolvedPartnership,
            $resolvedCoverage,
            formationCompletion: $resolvedFormationCompletion,
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
