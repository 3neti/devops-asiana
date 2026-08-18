<?php

use App\Http\Controllers\AuthorityMatrixController;
use App\Http\Controllers\BreakGlassAccessController;
use App\Http\Controllers\ChangeController;
use App\Http\Controllers\ClientAcceptanceController;
use App\Http\Controllers\ContinuityExerciseController;
use App\Http\Controllers\CorrectiveActionController;
use App\Http\Controllers\DecisionRecordController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\FirmConsoleController;
use App\Http\Controllers\GovernanceMeetingController;
use App\Http\Controllers\IdentityAndRoleController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InstitutionalDocumentController;
use App\Http\Controllers\PolicyRegistryController;
use App\Http\Controllers\ProductionAccessController;
use App\Http\Controllers\ResponsibilityCoverageController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', FirmConsoleController::class)->name('dashboard');
    Route::get('policies', PolicyRegistryController::class)->name('policy-registry.index');
    Route::get('client-acceptance', ClientAcceptanceController::class)->name('client-acceptance.index');
    Route::get('engagements', EngagementController::class)->name('engagements.index');
    Route::get('production-access', ProductionAccessController::class)->name('production-access.index');
    Route::get('break-glass-access', BreakGlassAccessController::class)->name('break-glass-access.index');
    Route::get('changes', ChangeController::class)->name('changes.index');
    Route::get('incidents', IncidentController::class)->name('incidents.index');
    Route::get('corrective-actions', CorrectiveActionController::class)->name('corrective-actions.index');
    Route::get('continuity-exercises', ContinuityExerciseController::class)->name('continuity-exercises.index');
    Route::get('responsibility-coverage', ResponsibilityCoverageController::class)->name('responsibility-coverage.index');
    Route::get('identity-and-roles', IdentityAndRoleController::class)->name('identity-and-roles.index');
    Route::get('authority-matrix', AuthorityMatrixController::class)->name('authority-matrix.index');
    Route::get('decision-records', DecisionRecordController::class)->name('decision-records.index');
    Route::get('governance-meetings', GovernanceMeetingController::class)->name('governance-meetings.index');
    Route::get('documents/{document}', [InstitutionalDocumentController::class, 'show'])
        ->where('document', '.*')
        ->name('institutional-documents.show');
});

require __DIR__.'/settings.php';
