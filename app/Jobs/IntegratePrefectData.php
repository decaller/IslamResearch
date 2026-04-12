<?php

namespace App\Jobs;

use App\Models\LexiconRoot;
use App\Models\LexiconWord;
use App\Models\Sentence;
use App\Models\SentenceJob;
use App\Models\SentenceTranslation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class IntegratePrefectData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as failed.
     * Timeout chain: job (90s) < supervisor (120s) < queue retry_after (150s).
     */
    public int $tries = 3;

    public int $timeout = 90;

    /** @var int[] Seconds to wait between each retry (exponential backoff) */
    public array $backoff = [5, 30, 120];

    /**
     * @param array{
     *   sentence_job_id: string,
     *   sentence_id: string|null,
     *   status: string,
     *   embedding_ar: float[],
     *   embedding_id: float[],
     *   category: string|null,
     *   lexicon_data: array<array{word_raw: string, word_clean: string, root: string|null}>|null,
     * } $payload
     */
    public function __construct(public readonly array $payload) {}

    /**
     * Write enriched Prefect data into the Laravel database schema.
     *
     * This job handles:
     * - Updating sentence embeddings (embedding_ar, embedding_id)
     * - Saving the Indonesian translation into sentence_translations
     * - Upserting lexicon roots and words into the Global Lexicon
     * - Attaching word–sentence links via the sentence_word pivot
     * - Marking the SentenceJob as completed
     *
     * See: docs/architecture.md §Stage 4 & docs/lexicon-strategy.md §2
     */
    public function handle(): void
    {
        $sentenceJobId = $this->payload['sentence_job_id'];

        $sentenceJob = SentenceJob::findOrFail($sentenceJobId);

        // Guard: skip if already processed (idempotency)
        if ($sentenceJob->status === 'completed') {
            return;
        }

        DB::transaction(function () use ($sentenceJob): void {
            // 1. Update vector embeddings on the Sentence row
            if (isset($this->payload['sentence_id'])) {
                $sentence = Sentence::findOrFail($this->payload['sentence_id']);

                $sentence->update([
                    // PostgreSQL pgvector accepts arrays cast to a vector literal
                    'embedding_ar' => $this->vectorLiteral($this->payload['embedding_ar']),
                    'embedding_id' => $this->vectorLiteral($this->payload['embedding_id']),
                ]);

                // 2. Save Indonesian translation produced by the Aya model
                if (! empty($sentenceJob->metadata['indonesian'])) {
                    SentenceTranslation::firstOrCreate(
                        [
                            'sentence_id' => $sentence->id,
                            'language' => 'id',
                            'scholar_id' => null,
                        ],
                        ['translation_text' => $sentenceJob->metadata['indonesian'] ?? ''],
                    );
                }

                // 3. Upsert Global Lexicon (roots + words) and attach pivot links
                if (! empty($this->payload['lexicon_data'])) {
                    $this->integrateGlobalLexicon($sentence, $this->payload['lexicon_data']);
                }
            }

            // 4. Mark the tracking record as completed
            $sentenceJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'error_log' => null,
            ]);
        });
    }

    /**
     * Handle a permanent job failure by updating the SentenceJob status
     * so it appears in the Filament "Waiting Room" for human review.
     *
     * See: docs/architecture.md §SentenceJob (Batch Monitoring)
     */
    public function failed(Throwable $exception): void
    {
        SentenceJob::where('id', $this->payload['sentence_job_id'])
            ->whereNot('status', 'completed')
            ->update([
                'status' => 'failed',
                'error_log' => $exception->getMessage(),
            ]);
    }

    /**
     * Upsert lexicon roots and words, then attach them to the sentence
     * via the sentence_word pivot table.
     *
     * @param  array<array{word_raw: string, word_clean: string, root: string|null}>  $lexiconData
     */
    private function integrateGlobalLexicon(Sentence $sentence, array $lexiconData): void
    {
        $wordIds = [];

        foreach ($lexiconData as $index => $token) {
            // Upsert root if available
            $rootId = null;
            if (! empty($token['root'])) {
                $root = LexiconRoot::firstOrCreate(
                    ['language' => 'ar', 'root_value' => $token['root']],
                );
                $rootId = $root->id;
            }

            // Upsert surface word (raw + clean forms)
            $word = LexiconWord::firstOrCreate(
                [
                    'language' => 'ar',
                    'word_raw' => $token['word_raw'],
                    'word_clean' => $token['word_clean'],
                ],
                ['root_id' => $rootId],
            );

            $wordIds[$word->id] = [
                'source_type' => 'original',
                'positions' => [$index],  // Token position in the sentence
            ];
        }

        // Sync pivot without detaching existing entries from other sources
        $sentence->words()->syncWithoutDetaching($wordIds);
    }

    /**
     * Format a PHP float array as a PostgreSQL pgvector literal string.
     * Example: '[0.1, 0.2, ...]'
     */
    private function vectorLiteral(array $floats): string
    {
        return '['.implode(',', $floats).']';
    }
}
