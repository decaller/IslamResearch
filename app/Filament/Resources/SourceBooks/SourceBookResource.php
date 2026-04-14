<?php

namespace App\Filament\Resources\SourceBooks;

use App\Filament\Resources\SourceBooks\Pages\CreateSourceBook;
use App\Filament\Resources\SourceBooks\Pages\EditSourceBook;
use App\Filament\Resources\SourceBooks\Pages\ListSourceBooks;
use App\Filament\Resources\SourceBooks\Schemas\SourceBookForm;
use App\Filament\Resources\SourceBooks\Tables\SourceBooksTable;
use App\Models\SourceBook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SourceBookResource extends Resource
{
    protected static ?string $model = SourceBook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Data Sources';

    protected static ?int $navigationSort = 1;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return SourceBookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SourceBooksTable::configure($table);
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
            'index' => ListSourceBooks::route('/'),
            'create' => CreateSourceBook::route('/create'),
            'edit' => EditSourceBook::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
