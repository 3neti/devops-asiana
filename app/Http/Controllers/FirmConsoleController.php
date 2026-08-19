<?php

namespace App\Http\Controllers;

use App\FormationCompletion\FormationCompletionRepository;
use App\FormationCompletion\ResolveFormationCompletion;
use App\Partnership\CompilePartnershipAgreement;
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
        CompilePartnershipAgreement $compilePartnershipAgreement,
        ResolveFormationCompletion $resolveFormationCompletion,
    ): Response {
        $partnershipDefinition = $definitions->current();
        $resolvedPartnership = $resolvePartnership->handle($partnershipDefinition);

        return Inertia::render('FirmConsole/Index', [
            'partnershipAgreement' => $compilePartnershipAgreement->handle(
                $partnershipDefinition,
                $resolvedPartnership,
            )->toArray(),
            'formationCompletion' => $resolveFormationCompletion->handle(
                $formationCompletion->current(),
                $resolvedPartnership,
            )->toArray(),
            'partnership' => $resolvedPartnership->toArray(),
        ]);
    }
}
