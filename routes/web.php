<?php

use App\Http\Controllers\ClientAcceptanceController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\FirmConsoleController;
use App\Http\Controllers\InstitutionalDocumentController;
use App\Http\Controllers\PolicyRegistryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', FirmConsoleController::class)->name('dashboard');
    Route::get('policies', PolicyRegistryController::class)->name('policy-registry.index');
    Route::get('client-acceptance', ClientAcceptanceController::class)->name('client-acceptance.index');
    Route::get('engagements', EngagementController::class)->name('engagements.index');
    Route::get('documents/{document}', [InstitutionalDocumentController::class, 'show'])
        ->where('document', '.*')
        ->name('institutional-documents.show');
});

require __DIR__.'/settings.php';
