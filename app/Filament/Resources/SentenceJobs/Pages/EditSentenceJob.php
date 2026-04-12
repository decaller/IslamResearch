<?php

namespace App\Filament\Resources\SentenceJobs\Pages;

use App\Filament\Resources\SentenceJobs\SentenceJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSentenceJob extends EditRecord
{
    protected static string $resource = SentenceJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
