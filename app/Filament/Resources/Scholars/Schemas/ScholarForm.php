<?php

namespace App\Filament\Resources\Scholars\Schemas;

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
                TextInput::make('metadata'),
            ]);
    }
}
