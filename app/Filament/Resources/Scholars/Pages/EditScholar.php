<?php

namespace App\Filament\Resources\Scholars\Pages;

use App\Filament\Resources\Scholars\ScholarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScholar extends EditRecord
{
    protected static string $resource = ScholarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
