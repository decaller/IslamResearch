<?php

namespace App\Filament\Resources\LexiconRoots\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LexiconRootForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('language')
                    ->required(),
                TextInput::make('root_value')
                    ->required(),
                TextInput::make('metadata'),
                TextInput::make('embedding'),
            ]);
    }
}
