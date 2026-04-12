<?php

namespace App\Filament\Resources\LexiconWords\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LexiconWordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('root_id')
                    ->required(),
                TextInput::make('language')
                    ->required(),
                TextInput::make('word_raw')
                    ->required(),
                TextInput::make('word_clean')
                    ->required(),
                TextInput::make('metadata'),
                TextInput::make('embedding_raw'),
                TextInput::make('embedding_clean'),
            ]);
    }
}
