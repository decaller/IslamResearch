<?php

namespace App\Services;

use App\Models\SentenceJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrefectService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.prefect.url', 'http://prefect-server:4200/api');
    }

    /**
     * Trigger the Islamic Text Enrichment flow for a SPECIFIC job.
     */
    public function triggerEnrichment(SentenceJob $job): bool
    {
        try {
            $deploymentId = $this->getDeploymentIdByName('Islamic Text Enrichment', 'ingestion-deployment');

            if (! $deploymentId) {
                Log::warning("Prefect deployment 'ingestion-deployment' not found.");

                return false;
            }

            // Trigger the flow with parameters
            $response = Http::post("{$this->baseUrl}/deployments/{$deploymentId}/create_flow_run", [
                'name' => 'Sentence Enrichment: '.$job->id,
                'parameters' => [
                    'sentence_job_id' => $job->id,
                    'lang' => $job->target_language,
                    'scheme' => $job->transliteration_scheme,
                ],
                'state' => ['type' => 'SCHEDULED'],
            ]);

            if ($response->failed()) {
                Log::error('Failed to create Prefect flow run: '.$response->body());

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to trigger Prefect: '.$e->getMessage());

            return false;
        }
    }

    protected function getDeploymentIdByName(string $flowName, string $deploymentName): ?string
    {
        $response = Http::post("{$this->baseUrl}/deployments/filter", [
            'deployments' => [
                'operator' => 'and_',
                'name' => ['any_' => [$deploymentName]],
            ],
            'flows' => [
                'name' => ['any_' => [$flowName]],
            ],
        ]);

        if ($response->successful() && ! empty($response->json())) {
            // Find the one that matches the flow name explicitly if multiple returned
            return $response->json('0.id');
        }

        return null;
    }
}
