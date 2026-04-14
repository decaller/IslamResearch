<?php

namespace App\Filament\Resources\LexiconRoots;

use App\Filament\Resources\LexiconRoots\Pages\CreateLexiconRoot;
use App\Filament\Resources\LexiconRoots\Pages\EditLexiconRoot;
use App\Filament\Resources\LexiconRoots\Pages\ListLexiconRoots;
use App\Filament\Resources\LexiconRoots\Schemas\LexiconRootForm;
use App\Filament\Resources\LexiconRoots\Tables\LexiconRootsTable;
use App\Models\LexiconRoot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LexiconRootResource extends Resource
{
    protected static ?string $model = LexiconRoot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Lexicon';

    public static function form(Schema $schema): Schema
    {
        return LexiconRootForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LexiconRootsTable::configure($table);
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
            'index' => ListLexiconRoots::route('/'),
            'create' => CreateLexiconRoot::route('/create'),
            'edit' => EditLexiconRoot::route('/{record}/edit'),
        ];
    }
}
