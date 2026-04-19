<?php

namespace App\Filament\Resources\UserNotebooks;

use App\Filament\Resources\UserNotebooks\Pages\CreateUserNotebook;
use App\Filament\Resources\UserNotebooks\Pages\EditUserNotebook;
use App\Filament\Resources\UserNotebooks\Pages\ListUserNotebooks;
use App\Filament\Resources\UserNotebooks\Schemas\UserNotebookForm;
use App\Filament\Resources\UserNotebooks\Tables\UserNotebooksTable;
use App\Models\UserNotebook;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserNotebookResource extends Resource
{
    protected static ?string $model = UserNotebook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserNotebookForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserNotebooksTable::configure($table);
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
            'index' => ListUserNotebooks::route('/'),
            'create' => CreateUserNotebook::route('/create'),
            'edit' => EditUserNotebook::route('/{record}/edit'),
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
