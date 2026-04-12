<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrefectWebhookRequest;
use App\Jobs\IntegratePrefectData;
use Illuminate\Http\Response;

class PrefectWebhookController extends Controller
{
    /**
     * Receive an enriched sentence payload from the Prefect AI pipeline.
     *
     * This endpoint is intentionally thin: it validates the incoming payload,
     * returns 202 Accepted immediately, and offloads all database writes to an
     * IntegratePrefectData Horizon job on the dedicated `ai-callbacks` queue.
     *
     * See: docs/architecture.md §Stage 4
     */
    public function __invoke(PrefectWebhookRequest $request): Response
    {
        IntegratePrefectData::dispatch($request->validated())
            ->onQueue('ai-callbacks');

        return response()->noContent(Response::HTTP_ACCEPTED);
    }
}
