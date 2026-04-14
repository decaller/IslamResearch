<?php

namespace App\Filament\Resources\Sentences\Schemas;

use App\Enums\ResourceType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SentenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_book_id')
                    ->required(),
                Select::make('resource_type')
                    ->options(ResourceType::class)
                    ->required(),
                TextInput::make('sequence_number')
                    ->required()
                    ->numeric(),
                Textarea::make('sentence_text')
                    ->required()
                    ->columnSpanFull(),

                Section::make('AI Enrichment Results')
                    ->schema([
                        Repeater::make('translations')
                            ->relationship('translations')
                            ->schema([
                                TextInput::make('language')
                                    ->required()
                                    ->label('Lang')
                                    ->default('id'),
                                Textarea::make('translation_text')
                                    ->required()
                                    ->label('Text')
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                            ->label('Translations'),

                        Repeater::make('transliterations')
                            ->relationship('transliterations')
                            ->schema([
                                TextInput::make('scheme')
                                    ->required()
                                    ->label('Scheme')
                                    ->default('ala_lc'),
                                Textarea::make('transliteration_text')
                                    ->required()
                                    ->label('Text')
                                    ->rows(2)
                                    ->columnSpan(3),
                            ])
                            ->columns(4)
                            ->columnSpanFull()
                            ->label('Transliterations'),
                    ])
                    ->columns(1),

                Textarea::make('metadata')
                    ->columnSpanFull()
                    ->rows(10)
                    ->rule('json')
                    ->afterStateHydrated(fn ($state, $set) => $set('metadata', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)))
                    ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                    ->extraInputAttributes(['style' => 'font-family: monospace']),
                TextInput::make('embedding_ar'),
            ]);
    }
}
