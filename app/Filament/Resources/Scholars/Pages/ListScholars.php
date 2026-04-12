<?php

namespace App\Filament\Resources\Scholars\Pages;

use App\Filament\Resources\Scholars\ScholarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScholars extends ListRecords
{
    protected static string $resource = ScholarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
