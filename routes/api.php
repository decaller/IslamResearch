<?php

use App\Http\Controllers\PrefectWebhookController;
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
