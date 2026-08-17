<?php

namespace App\Http\Controllers;

use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use Inertia\Inertia;
use Inertia\Response;

class FirmConsoleController extends Controller
{
    public function __invoke(
        PartnershipDefinitionRepository $definitions,
        ResolvePartnership $resolvePartnership,
    ): Response {
        return Inertia::render('FirmConsole/Index', [
            'partnership' => $resolvePartnership->handle($definitions->current())->toArray(),
        ]);
    }
}
