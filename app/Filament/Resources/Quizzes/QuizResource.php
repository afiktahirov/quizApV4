<?php

namespace App\Filament\Resources\Quizzes;

use App\Filament\Resources\Quizzes\Pages\CreateQuiz;
use App\Filament\Resources\Quizzes\Pages\EditQuiz;
use App\Filament\Resources\Quizzes\Pages\ListQuizzes;
use App\Filament\Resources\Quizzes\Pages\ViewQuiz;
use App\Filament\Resources\Quizzes\Schemas\QuizForm;
use App\Filament\Resources\Quizzes\Tables\QuizzesTable;
use App\Filament\Resources\Quizzes\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\Quizzes\RelationManagers\SessionsRelationManager;
use App\Models\Quiz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Kampanyalar';



    public static function getLabel(): string
    {
        return 'Kampanya';
    }

    public static function getPluralLabel(): string
    {
        return 'Kampanyalar';
    }

    protected static ?string $recordTitleAttribute = 'Quiz';

    public static function form(Schema $schema): Schema
    {
        return QuizForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizzesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
            SessionsRelationManager::class,
        ];
    }



    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Admin hər şeyi görsün
        if (auth()->user()->is_admin ?? false) {
            return $query;
        }

        $merchantId = auth()->user()->merchant_id;

        return $query->whereHas('merchants', fn ($q) =>
        $q->where('merchant_id', $merchantId)
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit' => EditQuiz::route('/{record}/edit'),
            'view' => ViewQuiz::route('/{record}'),
        ];
    }



    public static function canViewAny(): bool
    {
        return true; // Hər kəs görə bilər (list səhifəsi)
    }

    public static function canView($record): bool
    {
        return true; // Hər kəs baxa bilər (view səhifəsi)
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'super_admin'; // Yalnız admin yarada bilər
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'super_admin'; // Yalnız admin dəyişə bilər
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'super_admin'; // Yalnız admin silə bilər
    }
    


}
