<?php

namespace App\Filament\Resources\Scholars\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScholarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
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
