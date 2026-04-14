<?php

namespace App\Filament\Resources\SentenceJobs\Tables;

use App\Jobs\DispatchPrefectEnrichment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class SentenceJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->limit(8),
                TextColumn::make('sentence.sentence_text')
                    ->label('Text')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('needs_embedding')
                    ->boolean()
                    ->label('Emb'),
                IconColumn::make('needs_translation')
                    ->boolean()
                    ->label('Trans'),
                IconColumn::make('needs_transliteration')
                    ->boolean()
                    ->label('Tl'),
                TextColumn::make('attempts')
                    ->numeric(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->label('Last Activity'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->hidden(fn ($record) => $record->status === 'completed')
                    ->action(function ($record) {
                        $record->update(['status' => 'pending']);
                        DispatchPrefectEnrichment::dispatch($record)
                            ->onQueue('ai-enrichment');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('retrySelected')
                        ->label('Retry Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status !== 'completed') {
                                    $record->update(['status' => 'pending']);
                                    DispatchPrefectEnrichment::dispatch($record)
                                        ->onQueue('ai-enrichment');
                                }
                            });

                            Notification::make()
                                ->title('Retries Queued')
                                ->body($records->count().' jobs have been sent back to the queue.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
