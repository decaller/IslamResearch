<?php

namespace App\Jobs;

use App\Enums\ResourceType;
use App\Models\Scholar;
use App\Models\Sentence;
use App\Models\SentenceTranslation;
use App\Models\SourceBook;
use App\Services\AlQuranApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportQuranSurahJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout chain: job (120s) < supervisor (150s) < queue retry_after (180s).
     */
    public int $tries = 3;

    public int $timeout = 120;

    /** @var int[] Exponential backoff in seconds between retries */
    public array $backoff = [5, 30, 120];

    /**
     * @param  string[]  $translationEditions  Edition identifiers for translations (e.g. ['en.pickthall', 'id.indonesian'])
     */
    public function __construct(
        public readonly int $surahNumber,
        public readonly string $arabicEdition,
        public readonly array $translationEditions,
        public readonly string $cacheProgressKey,
    ) {}

    /**
     * Import one surah (Arabic sentences + selected translations) into the database.
     *
     * Flow:
     *  1. Fetch Arabic surah from the API
     *  2. Find-or-create the Arabic SourceBook
     *  3. Upsert all ayahs as Sentences (with full metadata)
     *  4. For each translation edition, fetch & upsert SentenceTranslations
     *  5. Update the cache progress key so the UI can poll progress
     */
    public function handle(AlQuranApiService $api): void
    {
        $arabicSurah = $api->getSurah($this->surahNumber, $this->arabicEdition);

        $arabicSourceBook = $this->resolveArabicSourceBook($arabicSurah);

        DB::transaction(function () use ($arabicSurah, $arabicSourceBook, $api): void {
            $this->upsertAyahs($arabicSourceBook, $arabicSurah['ayahs']);

            foreach ($this->translationEditions as $edition) {
                $translationSurah = $api->getSurah($this->surahNumber, $edition);
                $scholar = $this->resolveApiScholar();
                $language = $translationSurah['edition']['language'] ?? 'en';

                $this->upsertTranslations($arabicSourceBook, $translationSurah['ayahs'], $language, $scholar->id, $edition);
            }
        });

        $this->updateProgress();
    }

    /**
     * Mark the surah as failed in the cache progress key so the UI surfaces it.
     */
    public function failed(Throwable $exception): void
    {
        $progress = Cache::get($this->cacheProgressKey, []);
        $progress['failed'][$this->surahNumber] = $exception->getMessage();
        Cache::put($this->cacheProgressKey, $progress, now()->addHours(2));
    }

    /**
     * Find or create the SourceBook representing this Arabic Quran edition.
     *
     * @param  array<string, mixed>  $arabicSurah
     */
    private function resolveArabicSourceBook(array $arabicSurah): SourceBook
    {
        $editionName = $arabicSurah['edition']['englishName'] ?? $this->arabicEdition;

        return SourceBook::firstOrCreate(
            [
                'resource_type' => ResourceType::Quran->value,
                'language' => 'ar',
                'title' => $editionName,
            ],
            [
                'status' => 'active',
                'metadata' => [
                    'source' => 'alquran.cloud',
                    'edition_identifier' => $this->arabicEdition,
                    'edition_name' => $editionName,
                ],
            ],
        );
    }

    /**
     * Find or create the system Scholar representing the alquran.cloud API source.
     */
    private function resolveApiScholar(): Scholar
    {
        return Scholar::firstOrCreate(
            ['name' => 'Al Quran Cloud API'],
            [
                'metadata' => [
                    'source' => 'https://alquran.cloud',
                    'description' => 'System scholar representing data imported via the alquran.cloud public API.',
                ],
            ],
        );
    }

    /**
     * Upsert all ayahs of a surah as Sentence rows.
     *
     * @param  array<int, array<string, mixed>>  $ayahs
     */
    private function upsertAyahs(SourceBook $sourceBook, array $ayahs): void
    {
        foreach ($ayahs as $ayah) {
            Sentence::updateOrCreate(
                [
                    'source_book_id' => $sourceBook->id,
                    'sequence_number' => $ayah['number'],
                ],
                [
                    'resource_type' => ResourceType::Quran->value,
                    'sentence_text' => $ayah['text'],
                    'metadata' => $this->buildAyahMetadata($ayah),
                ],
            );
        }
    }

    /**
     * Upsert translation rows for all ayahs in one edition.
     *
     * @param  array<int, array<string, mixed>>  $ayahs
     */
    private function upsertTranslations(
        SourceBook $arabicSourceBook,
        array $ayahs,
        string $language,
        string $scholarId,
        string $editionIdentifier,
    ): void {
        foreach ($ayahs as $ayah) {
            /** @var Sentence|null $sentence */
            $sentence = Sentence::where('source_book_id', $arabicSourceBook->id)
                ->where('sequence_number', $ayah['number'])
                ->first();

            if ($sentence === null) {
                continue;
            }

            SentenceTranslation::updateOrCreate(
                [
                    'sentence_id' => $sentence->id,
                    'language' => $language,
                    'scholar_id' => $scholarId,
                ],
                [
                    'translation_text' => $ayah['text'],
                ],
            );
        }
    }

    /**
     * Build the metadata JSON blob for an ayah sentence row.
     *
     * @param  array<string, mixed>  $ayah
     * @return array<string, mixed>
     */
    private function buildAyahMetadata(array $ayah): array
    {
        return array_filter([
            'source' => 'alquran.cloud',
            'edition' => $this->arabicEdition,
            'global_ayah_number' => $ayah['number'] ?? null,
            'surah_number' => $ayah['surah']['number'] ?? null,
            'surah_name_ar' => $ayah['surah']['name'] ?? null,
            'surah_name_en' => $ayah['surah']['englishName'] ?? null,
            'surah_name_translation' => $ayah['surah']['englishNameTranslation'] ?? null,
            'surah_revelation_type' => $ayah['surah']['revelationType'] ?? null,
            'ayah_number_in_surah' => $ayah['numberInSurah'] ?? null,
            'juz' => $ayah['juz'] ?? null,
            'manzil' => $ayah['manzil'] ?? null,
            'page' => $ayah['page'] ?? null,
            'ruku' => $ayah['ruku'] ?? null,
            'hizb_quarter' => $ayah['hizbQuarter'] ?? null,
            'sajda' => $ayah['sajda'] ?? null,
        ]);
    }

    /**
     * Increment the "completed" surah count in the shared cache progress key.
     */
    private function updateProgress(): void
    {
        $progress = Cache::get($this->cacheProgressKey, ['completed' => [], 'failed' => []]);
        $progress['completed'][] = $this->surahNumber;
        Cache::put($this->cacheProgressKey, $progress, now()->addHours(2));
    }
}
