<?php

namespace App\Http\Controllers;

use App\ClientAcceptance\ClientAcceptanceRepository;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\Engagements\EngagementRepository;
use App\Engagements\ResolveEngagements;
use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryRepository;
use App\Policies\ResolvePolicyRegistry;
use App\ProductionAccess\ProductionAccessRepository;
use App\ProductionAccess\ResolveProductionAccess;
use Inertia\Inertia;
use Inertia\Response;

class ProductionAccessController extends Controller
{
    public function __invoke(
        ProductionAccessRepository $productionAccess,
        EngagementRepository $engagements,
        ClientAcceptanceRepository $clientAcceptance,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
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

        return Inertia::render('ProductionAccess/Index', [
            'productionAccess' => $resolveProductionAccess->handle(
                $productionAccess->current(),
                $resolvedEngagements,
                $resolvedPolicies,
            )->toArray(),
        ]);
    }
}
