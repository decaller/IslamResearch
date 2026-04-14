<?php

namespace App\Filament\Resources\Taxonomies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Section::make('Metadata')
                    ->schema([
                        Textarea::make('metadata')
                            ->columnSpanFull()
                            ->rows(10)
                            ->rule('json')
                            ->afterStateHydrated(fn ($state, $set) => $set('metadata', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)))
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                            ->extraInputAttributes(['style' => 'font-family: monospace']),
                    ]),
                TextInput::make('path')
                    ->label('Hierarchy Path (LTree)')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Automatically generated'),
            ]);
    }
}
