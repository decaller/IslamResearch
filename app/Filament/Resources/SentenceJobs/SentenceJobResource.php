<?php

namespace App\Filament\Resources\SentenceJobs;

use App\Filament\Resources\SentenceJobs\Pages\CreateSentenceJob;
use App\Filament\Resources\SentenceJobs\Pages\EditSentenceJob;
use App\Filament\Resources\SentenceJobs\Pages\ListSentenceJobs;
use App\Filament\Resources\SentenceJobs\Schemas\SentenceJobForm;
use App\Filament\Resources\SentenceJobs\Tables\SentenceJobsTable;
use App\Models\SentenceJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SentenceJobResource extends Resource
{
    protected static ?string $model = SentenceJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SentenceJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SentenceJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentenceJobs::route('/'),
            'create' => CreateSentenceJob::route('/create'),
            'edit' => EditSentenceJob::route('/{record}/edit'),
        ];
    }
}
