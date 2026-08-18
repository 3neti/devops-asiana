<?php

namespace App\Http\Controllers;

use App\AuthorityMatrix\AuthorityMatrixRepository;
use App\AuthorityMatrix\ResolveAuthorityMatrix;
use App\DecisionRecords\DecisionRecordRepository;
use App\DecisionRecords\ResolveDecisionRecords;
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
        AuthorityMatrixRepository $authorityMatrix,
        IdentityAndRoleRepository $identityAndRoles,
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveDecisionRecords $resolveDecisionRecords,
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

        return Inertia::render('DecisionRecords/Index', [
            'decisionRecords' => $resolveDecisionRecords->handle(
                $decisionRecords->current(),
                $resolvedPolicies,
                $resolvedAuthority,
            )->toArray(),
        ]);
    }
}
