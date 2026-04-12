<?php

namespace App\Filament\Resources\LexiconWords\Pages;

use App\Filament\Resources\LexiconWords\LexiconWordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLexiconWord extends EditRecord
{
    protected static string $resource = LexiconWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
