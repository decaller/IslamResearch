<?php

namespace App\Filament\Resources\Scholars;

use App\Filament\Resources\Scholars\Pages\CreateScholar;
use App\Filament\Resources\Scholars\Pages\EditScholar;
use App\Filament\Resources\Scholars\Pages\ListScholars;
use App\Filament\Resources\Scholars\Schemas\ScholarForm;
use App\Filament\Resources\Scholars\Tables\ScholarsTable;
use App\Models\Scholar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScholarResource extends Resource
{
    protected static ?string $model = Scholar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ScholarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScholarsTable::configure($table);
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
            'index' => ListScholars::route('/'),
            'create' => CreateScholar::route('/create'),
            'edit' => EditScholar::route('/{record}/edit'),
        ];
    }
}
