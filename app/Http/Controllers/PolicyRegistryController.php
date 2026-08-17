<?php

namespace App\Http\Controllers;

use App\Policies\PolicyRegistryRepository;
use App\Policies\ResolvePolicyRegistry;
use Inertia\Inertia;
use Inertia\Response;

class PolicyRegistryController extends Controller
{
    public function __invoke(
        PolicyRegistryRepository $registries,
        ResolvePolicyRegistry $resolvePolicyRegistry,
    ): Response {
        return Inertia::render('Policies/Index', [
            'registry' => $resolvePolicyRegistry->handle($registries->current())->toArray(),
        ]);
    }
}
