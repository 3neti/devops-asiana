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
use Inertia\Inertia;
use Inertia\Response;

class EngagementController extends Controller
{
    public function __invoke(
        EngagementRepository $engagements,
        ClientAcceptanceRepository $clientAcceptance,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveEngagements $resolveEngagements,
        ResolveClientAcceptance $resolveClientAcceptance,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        $policyRegistry = $policies->current();

        return Inertia::render('Engagements/Index', [
            'engagements' => $resolveEngagements->handle(
                $engagements->current(),
                $resolveClientAcceptance->handle($clientAcceptance->current(), $policyRegistry),
                $resolvePartnership->handle($partnership->current()),
                $resolvePolicyRegistry->handle($policyRegistry),
            )->toArray(),
        ]);
    }
}
