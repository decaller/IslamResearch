<?php

namespace App\Filament\Resources\Sentences\Pages;

use App\Filament\Resources\Sentences\SentenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSentence extends EditRecord
{
    protected static string $resource = SentenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
