<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)->name('api.health');

Route::middleware(['auth.apiclient', 'throttle:api-client'])
    ->prefix('v1')
    ->group(function (): void {
        Route::post('/documents', [DocumentController::class, 'store'])->name('api.documents.store');
        Route::get('/documents/{uuid}', [DocumentController::class, 'show'])->name('api.documents.show');
        Route::get('/documents/{uuid}/content', [DocumentController::class, 'content'])->name('api.documents.content');
        Route::delete('/documents/{uuid}', [DocumentController::class, 'destroy'])->name('api.documents.destroy');
    });
