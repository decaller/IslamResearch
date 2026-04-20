<?php

use App\Http\Controllers\PrefectWebhookController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes here are automatically prefixed with /api and use the
| stateless 'api' middleware group (no sessions / CSRF).
|
*/

// Prefect AI Pipeline Webhook
// Receives enriched sentence payloads from the Python Prefect worker and
// immediately returns 202 Accepted while dispatching an IntegratePrefectData
// Horizon job to handle all database writes asynchronously.
// See: docs/architecture.md §Stage 4
Route::post('/webhooks/prefect/job-completed', PrefectWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.prefect.job-completed');

// Search Integration
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/search/pipeline', [SearchController::class, 'pipeline'])->name('search.pipeline');
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
});
