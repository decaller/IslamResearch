<?php

namespace App\Jobs;

use App\Models\SentenceJob;
use App\Services\PrefectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchPrefectEnrichment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public SentenceJob $sentenceJob) {}

    /**
     * Execute the job.
     */
    public function handle(PrefectService $prefect): void
    {
        // 1. Update status to processing
        $this->sentenceJob->update(['status' => 'processing', 'attempts' => $this->sentenceJob->attempts + 1]);

        // 2. Trigger Prefect with specific job_id
        $success = $prefect->triggerEnrichment($this->sentenceJob);

        if (! $success) {
            $this->sentenceJob->update(['status' => 'failed', 'error_log' => 'Could not reach Prefect API']);
            // If it failed to even reach Prefect, we should probably throw an exception to let Horizon retry
            throw new \Exception("Prefect trigger failed for job: {$this->sentenceJob->id}");
        }

        // Job completed on Laravel's side (it's now Prefect's responsibility)
        Log::info("Sent job {$this->sentenceJob->id} to Prefect.");
    }

    public function failed(\Throwable $exception): void
    {
        $this->sentenceJob->update([
            'status' => 'failed',
            'error_log' => $exception->getMessage(),
        ]);
    }
}
