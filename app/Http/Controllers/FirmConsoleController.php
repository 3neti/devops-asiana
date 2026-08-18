<?php

namespace App\Http\Controllers;

use App\FormationCompletion\FormationCompletionRepository;
use App\FormationCompletion\ResolveFormationCompletion;
use App\Partnership\PartnershipDefinitionRepository;
use App\Partnership\ResolvePartnership;
use Inertia\Inertia;
use Inertia\Response;

class FirmConsoleController extends Controller
{
    public function __invoke(
        PartnershipDefinitionRepository $definitions,
        FormationCompletionRepository $formationCompletion,
        ResolvePartnership $resolvePartnership,
        ResolveFormationCompletion $resolveFormationCompletion,
    ): Response {
        $resolvedPartnership = $resolvePartnership->handle($definitions->current());

        return Inertia::render('FirmConsole/Index', [
            'formationCompletion' => $resolveFormationCompletion->handle(
                $formationCompletion->current(),
                $resolvedPartnership,
            )->toArray(),
            'partnership' => $resolvedPartnership->toArray(),
        ]);
    }
}
