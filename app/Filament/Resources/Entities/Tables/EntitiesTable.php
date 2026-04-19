<?php

namespace App\Filament\Resources\Entities\Tables;

use App\Enums\EntityType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                //
            ]);
    }
}
