<?php

namespace App\Http\Controllers;

use App\Documentation\InstitutionalDocumentRepository;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionalDocumentController extends Controller
{
    public function show(string $document, InstitutionalDocumentRepository $documents): Response
    {
        $institutionalDocument = $documents->find($document);

        abort_if($institutionalDocument === null, 404);

        return Inertia::render('Documents/Show', [
            'document' => $institutionalDocument,
        ]);
    }
}
