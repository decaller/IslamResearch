<?php

namespace App\Filament\Resources\SourceBooks\Schemas;

use App\Enums\ResourceType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SourceBookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('scholar_id'),
                Select::make('resource_type')
                    ->options(ResourceType::class)
                    ->required(),
                TextInput::make('language')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                Textarea::make('metadata')
                    ->columnSpanFull()
                    ->rows(10)
                    ->rule('json')
                    ->afterStateHydrated(fn ($state, $set) => $set('metadata', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)))
                    ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                    ->extraInputAttributes(['style' => 'font-family: monospace']),
            ]);
    }
}
