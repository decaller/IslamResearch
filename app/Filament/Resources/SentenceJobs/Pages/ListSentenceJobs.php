<?php

namespace App\Filament\Resources\SentenceJobs\Pages;

use App\Filament\Resources\SentenceJobs\SentenceJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSentenceJobs extends ListRecords
{
    protected static string $resource = SentenceJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
