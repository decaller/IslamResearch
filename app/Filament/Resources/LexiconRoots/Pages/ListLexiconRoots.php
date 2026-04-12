<?php

namespace App\Filament\Resources\LexiconRoots\Pages;

use App\Filament\Resources\LexiconRoots\LexiconRootResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLexiconRoots extends ListRecords
{
    protected static string $resource = LexiconRootResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
