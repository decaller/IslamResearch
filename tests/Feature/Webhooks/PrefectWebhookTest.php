<?php

use App\Jobs\IntegratePrefectData;
use App\Models\Sentence;
use App\Models\SentenceJob;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper: build a valid 1024-dim float array
// ---------------------------------------------------------------------------
function validEmbedding(): array
{
    return array_fill(0, 1024, 0.001);
}

// Helper: build a complete, valid webhook payload
function validPayload(SentenceJob $job, Sentence $sentence): array
{
    return [
        'sentence_job_id' => $job->id,
        'sentence_id' => $sentence->id,
        'status' => 'completed',
        'category' => 'Fiqh',
        'embedding_ar' => validEmbedding(),
        'embedding_id' => validEmbedding(),
        'lexicon_data' => [
            ['word_raw' => 'يُؤْمِنُونَ', 'word_clean' => 'يؤمنون', 'root' => 'أ م ن'],
        ],
    ];
}

// ---------------------------------------------------------------------------
// Scenario 1: Valid payload → 202 Accepted
// ---------------------------------------------------------------------------
it('returns 202 for a valid prefect webhook payload', function (): void {
    Queue::fake();

    $job = SentenceJob::factory()->create(['status' => 'pending']);
    $sentence = Sentence::factory()->create();

    $this->postJson('/api/webhooks/prefect/job-completed', validPayload($job, $sentence))
        ->assertStatus(202);
});

// ---------------------------------------------------------------------------
// Scenario 2: Job is dispatched on the ai-callbacks queue
// ---------------------------------------------------------------------------
it('dispatches IntegratePrefectData onto the ai-callbacks queue', function (): void {
    Queue::fake();

    $job = SentenceJob::factory()->create(['status' => 'pending']);
    $sentence = Sentence::factory()->create();

    $this->postJson('/api/webhooks/prefect/job-completed', validPayload($job, $sentence));

    Queue::assertPushedOn('ai-callbacks', IntegratePrefectData::class);
});

// ---------------------------------------------------------------------------
// Scenario 3: Missing sentence_job_id → 422 Unprocessable
// ---------------------------------------------------------------------------
it('returns 422 when sentence_job_id is missing', function (): void {
    Queue::fake();

    $sentence = Sentence::factory()->create();

    $payload = [
        'sentence_id' => $sentence->id,
        'status' => 'completed',
        'embedding_ar' => validEmbedding(),
        'embedding_id' => validEmbedding(),
    ];

    $this->postJson('/api/webhooks/prefect/job-completed', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sentence_job_id');
});

// ---------------------------------------------------------------------------
// Scenario 4: Unknown sentence_job_id (not in DB) → 422
// ---------------------------------------------------------------------------
it('returns 422 when sentence_job_id does not exist in the database', function (): void {
    Queue::fake();

    $sentence = Sentence::factory()->create();

    $payload = validPayload(
        SentenceJob::factory()->make(), // make() does not persist to DB
        $sentence,
    );
    $payload['sentence_job_id'] = '00000000-0000-0000-0000-000000000000';

    $this->postJson('/api/webhooks/prefect/job-completed', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sentence_job_id');
});

// ---------------------------------------------------------------------------
// Scenario 5: Wrong embedding dimensions (512 instead of 1024) → 422
// ---------------------------------------------------------------------------
it('returns 422 when embedding_ar has wrong dimensions', function (): void {
    Queue::fake();

    $job = SentenceJob::factory()->create(['status' => 'pending']);
    $sentence = Sentence::factory()->create();

    $payload = validPayload($job, $sentence);
    $payload['embedding_ar'] = array_fill(0, 512, 0.001); // Wrong: 512-dim

    $this->postJson('/api/webhooks/prefect/job-completed', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('embedding_ar');
});

// ---------------------------------------------------------------------------
// Scenario 6: Malformed JSON blob (LLM hallucination — non-numeric values)
// ---------------------------------------------------------------------------
it('returns 422 when embedding values are non-numeric (malformed LLM output)', function (): void {
    Queue::fake();

    $job = SentenceJob::factory()->create(['status' => 'pending']);
    $sentence = Sentence::factory()->create();

    $payload = validPayload($job, $sentence);
    // Simulate an LLM hallucinating strings instead of floats
    $payload['embedding_id'] = array_fill(0, 1024, 'not-a-float');

    $this->postJson('/api/webhooks/prefect/job-completed', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('embedding_id.0');
});

// ---------------------------------------------------------------------------
// Scenario 7: IntegratePrefectData failed() → SentenceJob status = 'failed'
// ---------------------------------------------------------------------------
it('marks SentenceJob as failed when the Horizon job permanently fails', function (): void {
    $job = SentenceJob::factory()->create(['status' => 'pending']);

    $payload = [
        'sentence_job_id' => $job->id,
        'sentence_id' => null,
        'status' => 'completed',
        'embedding_ar' => validEmbedding(),
        'embedding_id' => validEmbedding(),
        'lexicon_data' => [],
    ];

    $horizonJob = new IntegratePrefectData($payload);
    $horizonJob->failed(new RuntimeException('Simulated Horizon failure'));

    $this->assertDatabaseHas('sentence_jobs', [
        'id' => $job->id,
        'status' => 'failed',
    ]);
});
