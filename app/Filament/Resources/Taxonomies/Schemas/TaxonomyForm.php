<?php

namespace App\Filament\Resources\Taxonomies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TaxonomyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Parent Taxonomy')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select a parent category'),
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Section::make('Multilingual Names')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('metadata.name_ar')
                                    ->label('Arabic Name')
                                    ->extraInputAttributes(['dir' => 'rtl']),
                                TextInput::make('metadata.name_en')
                                    ->label('English Name'),
                                TextInput::make('metadata.name_transliteration')
                                    ->label('Transliteration'),
                            ]),
                    ]),
                TextInput::make('path')
                    ->label('Hierarchy Path (LTree)')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Automatically generated'),
            ]);
    }
}
