<?php

namespace App\Filament\Resources\LexiconWords;

use App\Filament\Resources\LexiconWords\Pages\CreateLexiconWord;
use App\Filament\Resources\LexiconWords\Pages\EditLexiconWord;
use App\Filament\Resources\LexiconWords\Pages\ListLexiconWords;
use App\Filament\Resources\LexiconWords\Schemas\LexiconWordForm;
use App\Filament\Resources\LexiconWords\Tables\LexiconWordsTable;
use App\Models\LexiconWord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LexiconWordResource extends Resource
{
    protected static ?string $model = LexiconWord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Lexicon';

    public static function form(Schema $schema): Schema
    {
        return LexiconWordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LexiconWordsTable::configure($table);
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
            'index' => ListLexiconWords::route('/'),
            'create' => CreateLexiconWord::route('/create'),
            'edit' => EditLexiconWord::route('/{record}/edit'),
        ];
    }
}
