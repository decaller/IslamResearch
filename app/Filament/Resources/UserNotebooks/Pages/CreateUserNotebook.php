<?php

namespace App\Filament\Resources\UserNotebooks\Pages;

use App\Filament\Resources\UserNotebooks\UserNotebookResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserNotebook extends CreateRecord
{
    protected static string $resource = UserNotebookResource::class;
}
