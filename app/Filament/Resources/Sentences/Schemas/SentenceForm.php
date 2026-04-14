<?php

namespace App\Filament\Resources\Sentences\Schemas;

use App\Enums\ResourceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
