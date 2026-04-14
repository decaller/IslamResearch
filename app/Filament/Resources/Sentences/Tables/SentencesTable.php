<?php

namespace App\Filament\Resources\Sentences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SentencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('sentence_text')
                    ->label('Arabic Text')
                    ->searchable()
                    ->wrap()
                    ->extraAttributes(['dir' => 'rtl', 'class' => 'text-right font-arabic']),

                TextColumn::make('metadata.category')
                    ->label('Domain')
                    ->badge()
                    ->color('success'),

                TextColumn::make('metadata.tags')
                    ->label('Scholar Tags')
                    ->badge()
                    ->separator(',')
                    ->color('info')
                    ->wrap(),

                TextColumn::make('translations.translation_text')
                    ->label('Translations')
                    ->bulleted()
                    ->wrap(),

                TextColumn::make('transliterations.transliteration_text')
                    ->label('Transliterations')
                    ->bulleted()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                TextColumn::make('embedding_ar')
                    ->label('Arabic Vector')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(15),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
