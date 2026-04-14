<?php

namespace App\Services;

use App\Models\SentenceJob;
use App\Models\SourceBook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrefectService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://prefect-server:4200/api';
    }

    /**
     * Trigger the Islamic Text Ingestion flow for a specific SourceBook.
     */
    public function triggerIngestion(SourceBook $sourceBook): bool
    {
        try {
            // 1. Prepare the SentenceJob if it doesn't exist
            $job = SentenceJob::updateOrCreate(
                [
                    'source_book_id' => $sourceBook->id,
                    'status' => 'pending',
                ],
                [
                    'raw_text' => $sourceBook->title . " (Metadata: " . json_encode($sourceBook->metadata) . ")",
                ]
            );

            // 2. Trigger Prefect Deployment via API
            $deploymentId = $this->getDeploymentIdByName('Islamic Text Ingestion', 'ingestion-deployment');

            if (!$deploymentId) {
                Log::warning("Prefect deployment 'ingestion-deployment' not found. Please ensure ai-pipeline is serving.");
                return false;
            }

            $response = Http::post("{$this->baseUrl}/deployments/{$deploymentId}/create_flow_run", [
                'name' => "Manual Trigger: " . $sourceBook->title,
                'state' => ['type' => 'SCHEDULED'],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to trigger Prefect: " . $e->getMessage());
            return false;
        }
    }

    protected function getDeploymentIdByName(string $flowName, string $deploymentName): ?string
    {
        $response = Http::post("{$this->baseUrl}/deployments/filter", [
            'deployments' => [
                'name' => ['any_' => [$deploymentName]]
            ]
        ]);

        if ($response->successful()) {
            return $response->json('0.id');
        }

        return null;
    }
}
