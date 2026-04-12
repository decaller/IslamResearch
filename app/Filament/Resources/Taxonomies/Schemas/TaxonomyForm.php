<?php

namespace App\Filament\Resources\Taxonomies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaxonomyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('parent_id'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('metadata'),
                TextInput::make('path'),
            ]);
    }
}
