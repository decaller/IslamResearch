<?php

namespace App\Filament\Resources\SentenceJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SentenceJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('source_book_id')
                    ->required(),
                Textarea::make('raw_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('error_log')
                    ->columnSpanFull(),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
