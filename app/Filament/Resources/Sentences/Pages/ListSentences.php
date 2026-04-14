<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\SentenceResource;
use App\Jobs\DispatchPrefectEnrichment;
use App\Models\Sentence;
use App\Models\SentenceJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSentences extends ListRecords
{
    protected static string $resource = SentenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('processWithAi')
                ->label('Process with AI')
                ->icon('heroicon-o-beaker')
                ->color('warning')
                ->form([
                    Select::make('target_languages')
                        ->label('Translation Languages')
                        ->options([
                            'id' => 'Indonesian',
                            'en' => 'English',
                            'fr' => 'French',
                            'ar-latn' => 'Arabic (Latin)',
                        ])
                        ->multiple()
                        ->default(['id'])
                        ->required(),
                    Select::make('transliteration_schemes')
                        ->label('Transliteration Schemes')
                        ->options([
                            'ala_lc' => 'ALA-LC',
                            'iso' => 'ISO 233',
                            'scientific' => 'Scientific Journal',
                        ])
                        ->multiple()
                        ->default(['ala_lc'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $langs = $data['target_languages'];
                    $schemes = $data['transliteration_schemes'];
                    $totalJobs = 0;

                    foreach ($langs as $lang) {
                        foreach ($schemes as $scheme) {
                            // Fetch sentences needing help for THIS specific request AND NOT already in queue for THIS combination
                            $sentences = Sentence::where(function ($query) use ($lang, $scheme) {
                                $query->whereNull('embedding_ar')
                                    ->orWhereDoesntHave('translations', fn ($q) => $q->where('language', $lang))
                                    ->orWhereDoesntHave('transliterations', fn ($q) => $q->where('scheme', $scheme));
                            })
                            ->whereDoesntHave('sentenceJobs', function ($q) use ($lang, $scheme) {
                                $q->whereIn('status', ['pending', 'processing'])
                                  ->where('target_language', $lang)
                                  ->where('transliteration_scheme', $scheme);
                            })
                            ->get();

                            foreach ($sentences as $sentence) {
                                $job = SentenceJob::create([
                                    'sentence_id' => $sentence->id,
                                    'status' => 'pending',
                                    'needs_embedding' => is_null($sentence->embedding_ar),
                                    'needs_translation' => ! $sentence->translations()->where('language', $lang)->exists(),
                                    'needs_transliteration' => ! $sentence->transliterations()->where('scheme', $scheme)->exists(),
                                    'target_language' => $lang,
                                    'transliteration_scheme' => $scheme,
                                    'attempts' => 0,
                                ]);

                                DispatchPrefectEnrichment::dispatch($job)
                                    ->onQueue('ai-enrichment');
                                
                                $totalJobs++;
                            }
                        }
                    }

                    if ($totalJobs === 0) {
                        Notification::make()
                            ->title('No work to do')
                            ->body("All selected combinations already exist. Alhamdulillah.")
                            ->info()
                            ->send();
                        return;
                    }

                    Notification::make()
                        ->title('AI Enrichment Queued')
                        ->body("Queued {$totalJobs} enrichment tasks across " . count($langs) . " languages.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
