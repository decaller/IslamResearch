<?php

namespace App\Filament\Resources\Entities\Tables;

use App\Models\Entity;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EntitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('canonical_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('entity_type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('aliases')
                    ->badge()
                    ->searchable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                BulkAction::make('merge')
                    ->label('Merge Entities')
                    ->icon('heroicon-o-squares-plus')
                    ->form([
                        Select::make('master_id')
                            ->label('Master Entity (Keep this one)')
                            ->options(fn (Collection $records) => $records->pluck('canonical_name', 'id'))
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $master = Entity::find($data['master_id']);
                        $others = $records->reject(fn ($record) => $record->id === $master->id);

                        foreach ($others as $other) {
                            $master->mergeWith($other);
                        }

                        Notification::make()
                            ->title('Entities merged successfully!')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]);
    }
}
