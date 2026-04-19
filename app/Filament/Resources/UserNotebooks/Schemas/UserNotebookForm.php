<?php

namespace App\Filament\Resources\UserNotebooks\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserNotebookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        MarkdownEditor::make('content_md')
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_published')
                            ->default(false),
                        Select::make('view_mode')
                            ->options([
                                'article' => 'Article',
                                'presentation' => 'Presentation',
                            ])
                            ->default('article')
                            ->required(),
                        Hidden::make('user_id')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
