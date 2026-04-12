<?php

namespace App\Filament\Resources\SourceBooks\Pages;

use App\Filament\Resources\SourceBooks\SourceBookResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSourceBook extends EditRecord
{
    protected static string $resource = SourceBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
