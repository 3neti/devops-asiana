<?php

namespace App\Http\Controllers;

use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use App\Policies\PolicyRegistryRepository;
use App\Policies\ResolvePolicyRegistry;
use App\ResponsibilityCoverage\ResolveResponsibilityCoverage;
use App\ResponsibilityCoverage\ResponsibilityCoverageRepository;
use Inertia\Inertia;
use Inertia\Response;

class ResponsibilityCoverageController extends Controller
{
    public function __invoke(
        ResponsibilityCoverageRepository $responsibilityCoverage,
        PartnershipDefinitionRepository $partnership,
        PolicyRegistryRepository $policies,
        ResolveResponsibilityCoverage $resolveResponsibilityCoverage,
        ResolvePartnership $resolvePartnership,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        return Inertia::render('ResponsibilityCoverage/Index', [
            'responsibilityCoverage' => $resolveResponsibilityCoverage->handle(
                $responsibilityCoverage->current(),
                $resolvePartnership->handle($partnership->current()),
                $resolvePolicyRegistry->handle($policies->current()),
            )->toArray(),
        ]);
    }
}
