<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\SentenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSentences extends ListRecords
{
    protected static string $resource = SentenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
