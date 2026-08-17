<?php

namespace App\Http\Controllers;

use App\ClientAcceptance\ClientAcceptanceRepository;
use App\ClientAcceptance\ResolveClientAcceptance;
use App\Policies\PolicyRegistryRepository;
use Inertia\Inertia;
use Inertia\Response;

class ClientAcceptanceController extends Controller
{
    public function __invoke(
        ClientAcceptanceRepository $clientAcceptance,
        PolicyRegistryRepository $policies,
        ResolveClientAcceptance $resolveClientAcceptance,
    ): Response {
        return Inertia::render('ClientAcceptance/Index', [
            'acceptance' => $resolveClientAcceptance
                ->handle($clientAcceptance->current(), $policies->current())
                ->toArray(),
        ]);
    }
}
