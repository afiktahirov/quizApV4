<?php

namespace App\Filament\Resources\QuizSessions;

use App\Filament\Resources\QuizSessions\Pages\CreateQuizSession;
use App\Filament\Resources\QuizSessions\Pages\EditQuizSession;
use App\Filament\Resources\QuizSessions\Pages\ListQuizSessions;
use App\Filament\Resources\QuizSessions\Pages\ViewQuizSession;
use App\Filament\Resources\QuizSessions\Schemas\QuizSessionForm;
use App\Filament\Resources\QuizSessions\Schemas\QuizSessionInfolist;
use App\Filament\Resources\QuizSessions\Tables\QuizSessionsTable;
use App\Models\QuizSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuizSessionResource extends Resource
{
    protected static ?string $model = QuizSession::class;

    protected static ?string $navigationLabel = 'Kampaniya iştirakçıları';


    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';


    public static function getLabel(): string
    {
        return 'İştirakçı';
    }

    public static function getPluralLabel(): string
    {
        return 'İştirakçılar';
    }



    protected static ?string $recordTitleAttribute = 'QuizSession';

    public static function form(Schema $schema): Schema
    {
        return QuizSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuizSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizSessionsTable::configure($table);
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
            'index' => ListQuizSessions::route('/'),
//            'create' => CreateQuizSession::route('/create'),
            'view' => ViewQuizSession::route('/{record}'),
//            'edit' => EditQuizSession::route('/{record}/edit'),
        ];
    }
}
