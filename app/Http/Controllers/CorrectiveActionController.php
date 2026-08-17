<?php

namespace App\Http\Controllers;

use App\BreakGlassAccess\BreakGlassAccessRepository;
use App\BreakGlassAccess\ResolveBreakGlassAccess;
use App\Changes\ChangeRepository;
use App\Changes\ResolveChanges;
use App\ClientAcceptance\ClientAcceptanceRepository;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\CorrectiveActions\CorrectiveActionRepository;
use App\CorrectiveActions\ResolveCorrectiveActions;
use App\Engagements\EngagementRepository;
use App\Engagements\ResolveEngagements;
use App\Incidents\IncidentRepository;
use App\Incidents\ResolveIncidents;
use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryRepository;
use App\Policies\ResolvePolicyRegistry;
use App\ProductionAccess\ProductionAccessRepository;
use App\ProductionAccess\ResolveProductionAccess;
use Inertia\Inertia;
use Inertia\Response;

class CorrectiveActionController extends Controller
{
    public function __invoke(
        CorrectiveActionRepository $correctiveActions,
        BreakGlassAccessRepository $breakGlassAccess,
        IncidentRepository $incidents,
        ChangeRepository $changes,
        ProductionAccessRepository $productionAccess,
        EngagementRepository $engagements,
        ClientAcceptanceRepository $clientAcceptance,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveCorrectiveActions $resolveCorrectiveActions,
        ResolveBreakGlassAccess $resolveBreakGlassAccess,
        ResolveIncidents $resolveIncidents,
        ResolveChanges $resolveChanges,
        ResolveProductionAccess $resolveProductionAccess,
        ResolveEngagements $resolveEngagements,
        ResolveClientAcceptance $resolveClientAcceptance,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        $policyDefinition = $policies->current();
        $resolvedPolicies = $resolvePolicyRegistry->handle($policyDefinition);
        $resolvedEngagements = $resolveEngagements->handle(
            $engagements->current(),
            $resolveClientAcceptance->handle($clientAcceptance->current(), $policyDefinition),
            $resolvePartnership->handle($partnership->current()),
            $resolvedPolicies,
        );
        $resolvedAccess = $resolveProductionAccess->handle($productionAccess->current(), $resolvedEngagements, $resolvedPolicies);
        $resolvedChanges = $resolveChanges->handle($changes->current(), $resolvedEngagements, $resolvedAccess, $resolvedPolicies);
        $resolvedIncidents = $resolveIncidents->handle($incidents->current(), $resolvedEngagements, $resolvedPolicies, $resolvedChanges);
        $resolvedBreakGlass = $resolveBreakGlassAccess->handle(
            $breakGlassAccess->current(),
            $resolvedEngagements,
            $resolvedIncidents,
            $resolvedPolicies,
        );

        return Inertia::render('CorrectiveActions/Index', [
            'correctiveActions' => $resolveCorrectiveActions->handle(
                $correctiveActions->current(),
                $resolvedIncidents,
                $resolvedChanges,
                $resolvedBreakGlass,
                $resolvedAccess,
                $resolvedPolicies,
            )->toArray(),
        ]);
    }
}
