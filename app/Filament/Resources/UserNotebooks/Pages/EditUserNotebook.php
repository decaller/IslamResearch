<?php

namespace App\Filament\Resources\UserNotebooks\Pages;

use App\Filament\Resources\UserNotebooks\UserNotebookResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUserNotebook extends EditRecord
{
    protected static string $resource = UserNotebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
