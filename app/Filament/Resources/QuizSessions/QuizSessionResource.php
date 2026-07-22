<?php

namespace App\Filament\Resources\QuizSessions;

use App\Filament\Resources\QuizSessions\Pages\ListQuizSessions;
use App\Filament\Resources\QuizSessions\Pages\ViewQuizSession;
use App\Filament\Resources\QuizSessions\Schemas\QuizSessionForm;
use App\Filament\Resources\QuizSessions\Schemas\QuizSessionInfolist;
use App\Filament\Resources\QuizSessions\Tables\QuizSessionsTable;
use App\Models\QuizSession;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizSessionResource extends Resource
{
    protected static ?string $model = QuizSession::class;

    protected static ?string $navigationLabel = 'Kampaniya iştirakçıları';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Kampaniyalar';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getLabel(): string
    {
        return 'İştirakçı';
    }

    public static function getPluralLabel(): string
    {
        return 'İştirakçılar';
    }

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
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['quiz', 'customer', 'store']);
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('merchant_id', $user?->merchant_id);
    }

    // Sessiyalar yalnız oxumaq üçündür — nəticələr paneldən dəyişdirilə bilməz
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizSessions::route('/'),
            'view'  => ViewQuizSession::route('/{record}'),
        ];
    }
}
