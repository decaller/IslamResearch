<?php

namespace App\Filament\Resources\LexiconRoots\Pages;

use App\Filament\Resources\LexiconRoots\LexiconRootResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLexiconRoot extends EditRecord
{
    protected static string $resource = LexiconRootResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
