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
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    use \App\Filament\Concerns\EnforcesPlanLimit;
    use \App\Filament\Concerns\RequiresActivePlan;

    public static function canViewAny(): bool
    {
        return static::merchantHasSelectedPlan();
    }

    protected static ?string $model = Quiz::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Kampanyalar';

    protected static string|\UnitEnum|null $navigationGroup = 'Kampaniyalar';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getLabel(): string
    {
        return 'Kampanya';
    }

    public static function getPluralLabel(): string
    {
        return 'Kampanyalar';
    }

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
        $user  = Filament::auth()->user();

        // Super admin hər şeyi görür, merchant yalnız öz kampaniyalarını
        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('merchant_id', $user?->merchant_id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListQuizzes::route('/'),
            'create' => CreateQuiz::route('/create'),
            'edit'   => EditQuiz::route('/{record}/edit'),
            'view'   => ViewQuiz::route('/{record}'),
        ];
    }

    /** Bu istifadəçi bu kampaniyanı idarə edə bilər? */
    public static function ownsRecord($record): bool
    {
        $user = Filament::auth()->user();

        return (bool) ($user?->is_admin
            || ($user?->merchant_id !== null && $record?->merchant_id === $user->merchant_id));
    }

    public static function canCreate(): bool
    {
        // merchant_admin: abunəlik aktiv VƏ paket kampaniya limiti dolmayıbsa
        return static::canCreateWithinPlan('quizzes');
    }

    public static function canEdit($record): bool
    {
        return static::ownsRecord($record);
    }

    public static function canDelete($record): bool
    {
        return static::ownsRecord($record);
    }
}
