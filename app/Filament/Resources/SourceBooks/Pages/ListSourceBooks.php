<?php

namespace App\Filament\Resources\SourceBooks\Pages;

use App\Filament\Resources\SourceBooks\SourceBookResource;
use App\Jobs\ImportQuranSurahJob;
use App\Services\AlQuranApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListSourceBooks extends ListRecords
{
    protected static string $resource = SourceBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importQuran')
                ->label('Import Quran')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->form([
                    Select::make('arabic_edition')
                        ->label('Arabic Edition')
                        ->options($this->arabicEditionOptions())
                        ->default('quran-uthmani')
                        ->required()
                        ->searchable(),
                    CheckboxList::make('translation_editions')
                        ->label('Translations')
                        ->options($this->translationEditionOptions())
                        ->searchable()
                        ->columns(2),
                    Grid::make(2)
                        ->schema([
                            Select::make('start_surah')
                                ->options($this->surahOptions())->default(1)->required(),
                            TextInput::make('start_ayah')
                                ->numeric()->default(1)->required(),
                            Select::make('end_surah')
                                ->options($this->surahOptions())->default(114)->required(),
                            TextInput::make('end_ayah')
                                ->numeric()->default(6)->required(),
                        ]),
                ])
                ->action(function (array $data) {
                    $startSurah = (int) $data['start_surah'];
                    $startAyah = (int) $data['start_ayah'];
                    $endSurah = (int) $data['end_surah'];
                    $endAyah = (int) $data['end_ayah'];

                    // Unique key just for tracking (though we don't have the bar now)
                    $progressKey = 'quran_import_'.Str::uuid();

                    for ($surahNumber = $startSurah; $surahNumber <= $endSurah; $surahNumber++) {
                        $sAyah = ($surahNumber === $startSurah) ? $startAyah : null;
                        $eAyah = ($surahNumber === $endSurah) ? $endAyah : null;

                        ImportQuranSurahJob::dispatch(
                            surahNumber: $surahNumber,
                            arabicEdition: $data['arabic_edition'],
                            translationEditions: $data['translation_editions'] ?? [],
                            cacheProgressKey: $progressKey,
                            startAyah: $sAyah,
                            endAyah: $eAyah,
                        );
                    }

                    Notification::make()
                        ->title('Import Started')
                        ->body('Queued '.(($endSurah - $startSurah) + 1).' surahs for processing.')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    private function surahOptions(): array
    {
        return collect(range(1, 114))->mapWithKeys(fn ($i) => [$i => "Surah $i"])->toArray();
    }

    private function arabicEditionOptions(): array
    {
        try {
            $editions = app(AlQuranApiService::class)->getEditions(format: 'text', language: 'ar');

            return collect($editions)->mapWithKeys(fn ($e) => [$e['identifier'] => $e['englishName']])->toArray();
        } catch (\Throwable) {
            return ['quran-uthmani' => 'Quran Uthmani'];
        }
    }

    private function translationEditionOptions(): array
    {
        try {
            $editions = app(AlQuranApiService::class)->getEditions(format: 'text', type: 'translation');

            return collect($editions)->mapWithKeys(fn ($e) => [$e['identifier'] => '['.strtoupper($e['language']).'] '.$e['englishName']])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
