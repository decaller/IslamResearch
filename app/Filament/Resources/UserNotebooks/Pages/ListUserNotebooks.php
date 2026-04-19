<?php

namespace App\Filament\Resources\UserNotebooks\Pages;

use App\Filament\Resources\UserNotebooks\UserNotebookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserNotebooks extends ListRecords
{
    protected static string $resource = UserNotebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
