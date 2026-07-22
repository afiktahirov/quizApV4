<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Questions\Pages\EditQuestion;
use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Filament\Resources\Questions\Pages\ViewQuestion;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Resources\Questions\Schemas\QuestionInfolist;
use App\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Models\Question;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;

class QuestionResource extends Resource
{
    use \App\Filament\Concerns\EnforcesPlanLimit;
    use \App\Filament\Concerns\RequiresActivePlan;

    public static function canViewAny(): bool
    {
        return static::merchantHasSelectedPlan();
    }

    protected static ?string $model = Question::class;

    protected static ?string $navigationLabel = 'Suallar';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Suallar';

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return 'Sual';
    }

    public static function getPluralLabel(): string
    {
        return 'Suallar';
    }

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    protected static ?string $recordTitleAttribute = null;

    public static function getRecordTitle(?EloquentModel $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        $t = $record->title ?? null;

        if (is_array($t)) {
            return $t['az'] ?? reset($t) ?? null;
        }

        return $t !== null ? (string) $t : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Super admin bütün sualları görür.
     * Merchant istifadəçisi: öz sualları + qlobal hazır baza.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereNull('merchant_id')
              ->orWhere('merchant_id', $user?->merchant_id);
        });
    }

    /** Sualın sahibi (qlobal sualları yalnız super admin idarə edir) */
    public static function ownsRecord($record): bool
    {
        $user = Filament::auth()->user();

        if ($user?->is_admin) {
            return true;
        }

        return $record?->merchant_id !== null
            && $record->merchant_id === $user?->merchant_id;
    }

    public static function canCreate(): bool
    {
        // merchant öz sual limitini keçə bilməz (qlobal baza sayılmır)
        return static::canCreateWithinPlan('questions');
    }

    public static function canEdit($record): bool
    {
        return static::ownsRecord($record);
    }

    public static function canDelete($record): bool
    {
        return static::ownsRecord($record);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'view'   => ViewQuestion::route('/{record}'),
            'edit'   => EditQuestion::route('/{record}/edit'),
        ];
    }
}
