<?php

namespace App\Filament\Resources\LexiconWords\Pages;

use App\Filament\Resources\LexiconWords\LexiconWordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLexiconWords extends ListRecords
{
    protected static string $resource = LexiconWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
