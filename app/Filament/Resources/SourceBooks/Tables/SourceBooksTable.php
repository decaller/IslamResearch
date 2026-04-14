<?php

namespace App\Filament\Resources\SourceBooks\Tables;

use App\Models\SourceBook;
use App\Services\PrefectService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SourceBooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('scholar_id'),
                TextColumn::make('resource_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('language')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('processWithAi')
                    ->label('Process with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->tooltip('Trigger the background AI pipeline for segmentation, root extraction, and vectorization.')
                    ->action(function (SourceBook $record) {
                        $success = app(PrefectService::class)->triggerIngestion($record);

                        if ($success) {
                            $record->update(['status' => 'processing']);
                            Notification::make()
                                ->title('AI Pipeline Triggered')
                                ->body('The processing flow has been sent to Prefect.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Trigger Failed')
                                ->body('Could not reach Prefect. Ensure the ai-pipeline container is healthy.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
