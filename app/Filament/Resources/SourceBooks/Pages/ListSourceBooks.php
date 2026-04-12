<?php

namespace App\Filament\Resources\SourceBooks\Pages;

use App\Filament\Resources\SourceBooks\SourceBookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSourceBooks extends ListRecords
{
    protected static string $resource = SourceBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
