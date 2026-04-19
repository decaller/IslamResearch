<?php

namespace App\Filament\Resources\Entities\Schemas;

use App\Enums\EntityType;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EntityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('canonical_name')
                ->required()
                ->unique(ignoreRecord: true),
            
            Select::make('entity_type')
                ->options(EntityType::class)
                ->required(),
            
            TagsInput::make('aliases')
                ->placeholder('Add common abbreviations or alternative names'),
            
            Textarea::make('description')
                ->rows(5),
            
            TextInput::make('wikipedia_url')
                ->url(),
            
            KeyValue::make('metadata')
                ->keyLabel('Attribute')
                ->valueLabel('Value'),
        ]);
    }
}
