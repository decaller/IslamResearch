<?php

namespace App\Filament\Pages;

use App\Jobs\ImportQuranSurahJob;
use App\Services\AlQuranApiService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * Filament admin page for importing the Holy Quran from the alquran.cloud API.
 *
 * Flow:
 *  1. Admin selects an Arabic edition + optional translation editions
 *  2. Clicks "Start Import" which dispatches 114 ImportQuranSurahJob jobs
 *  3. UI polls the cache key every 2 seconds to show live progress
 */
class ImportQuran extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.import-quran';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Import Quran';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Sources';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Import Quran from alquran.cloud';

    // ─── Form state ──────────────────────────────────────────────────────────

    /** @var array<string, mixed> */
    public array $data = [];

    // ─── Import progress state ────────────────────────────────────────────────

    public ?string $progressKey = null;

    public bool $isImporting = false;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'arabic_edition' => 'quran-uthmani',
            'translation_editions' => [],
            'skip_existing' => true,
        ]);

        // Resume progress display if a previous import is still running
        $stored = Cache::get('quran_import_active_key');
        if ($stored !== null && Cache::has($stored)) {
            $this->progressKey = $stored;
            $this->isImporting = true;
        }
    }

    // ─── Form definition ─────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Arabic Edition')
                    ->description('The primary Arabic text that will be stored as Sentences.')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->schema([
                        Select::make('arabic_edition')
                            ->label('Arabic Edition')
                            ->options($this->arabicEditionOptions())
                            ->default('quran-uthmani')
                            ->required()
                            ->searchable(),
                    ]),

                Section::make('Translation Editions')
                    ->description('Select one or more text translations to import alongside the Arabic. Each will be stored as SentenceTranslation rows attributed to "Al Quran Cloud API".')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->schema([
                        CheckboxList::make('translation_editions')
                            ->label('Translations')
                            ->options($this->translationEditionOptions())
                            ->searchable()
                            ->columns(3)
                            ->gridDirection('row'),
                    ]),

                Section::make('Options')
                    ->schema([
                        Toggle::make('skip_existing')
                            ->label('Skip if SourceBook already exists')
                            ->helperText('Safe re-run: only creates records that are missing.')
                            ->default(true),

                        Placeholder::make('stats')
                            ->label('What will be imported')
                            ->content('114 Surahs · 6,236 Ayahs · dispatched as 114 queued jobs'),
                    ]),
            ])
            ->statePath('data');
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startImport')
                ->label($this->isImporting ? 'Import Running…' : 'Start Import')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->disabled($this->isImporting)
                ->requiresConfirmation()
                ->modalHeading('Start Quran Import')
                ->modalDescription('This will dispatch 114 background jobs — one per Surah. The import runs in the background and you can monitor progress below. Continue?')
                ->modalSubmitActionLabel('Yes, start import')
                ->action('startImport'),
        ];
    }

    // ─── Handlers ─────────────────────────────────────────────────────────────

    public function startImport(): void
    {
        $state = $this->form->getState();

        $arabicEdition = $state['arabic_edition'];
        $translationEditions = $state['translation_editions'] ?? [];

        // Generate a unique cache key for this import run
        $this->progressKey = 'quran_import_'.Str::uuid()->toString();
        Cache::put($this->progressKey, ['completed' => [], 'failed' => []], now()->addHours(2));
        Cache::put('quran_import_active_key', $this->progressKey, now()->addHours(2));

        // Dispatch one job per surah (114 total)
        for ($surahNumber = 1; $surahNumber <= 114; $surahNumber++) {
            ImportQuranSurahJob::dispatch(
                surahNumber: $surahNumber,
                arabicEdition: $arabicEdition,
                translationEditions: $translationEditions,
                cacheProgressKey: $this->progressKey,
            );
        }

        $this->isImporting = true;

        Notification::make()
            ->title('Import started')
            ->body('114 jobs dispatched. Progress updates every 2 seconds below.')
            ->success()
            ->send();
    }

    public function resetImport(): void
    {
        if ($this->progressKey !== null) {
            Cache::forget($this->progressKey);
        }

        Cache::forget('quran_import_active_key');
        $this->progressKey = null;
        $this->isImporting = false;
    }

    // ─── Computed / polling ──────────────────────────────────────────────────

    /**
     * Called by wire:poll.2s to refresh progress from the cache.
     */
    #[On('refreshProgress')]
    public function refreshProgress(): void
    {
        // Livewire will re-render; progress() computed property does the work.
    }

    /**
     * Return live progress data from the cache.
     *
     * @return array{completed: int[], failed: array<int, string>}|null
     */
    #[Computed]
    public function progress(): ?array
    {
        if ($this->progressKey === null) {
            return null;
        }

        return Cache::get($this->progressKey);
    }

    /**
     * Percentage of surahs completed (0–100).
     */
    #[Computed]
    public function progressPercent(): int
    {
        $p = $this->progress();
        if ($p === null) {
            return 0;
        }

        return (int) round((count($p['completed']) / 114) * 100);
    }

    // ─── Option builders ─────────────────────────────────────────────────────

    /**
     * Return options for the Arabic edition selector.
     *
     * @return array<string, string>
     */
    private function arabicEditionOptions(): array
    {
        try {
            $editions = app(AlQuranApiService::class)->getEditions(format: 'text', language: 'ar');

            return collect($editions)
                ->mapWithKeys(fn (array $e) => [$e['identifier'] => $e['englishName'].' ('.$e['identifier'].')'])
                ->toArray();
        } catch (\Throwable) {
            // Fall back to the most common edition if the API is unavailable
            return ['quran-uthmani' => 'Quran Uthmani (quran-uthmani)'];
        }
    }

    /**
     * Return options for the translation CheckboxList, grouped by language.
     *
     * @return array<string, string>
     */
    private function translationEditionOptions(): array
    {
        try {
            $editions = app(AlQuranApiService::class)->getEditions(format: 'text', type: 'translation');

            return collect($editions)
                ->mapWithKeys(fn (array $e) => [
                    $e['identifier'] => '['.strtoupper($e['language']).'] '.$e['englishName'],
                ])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
