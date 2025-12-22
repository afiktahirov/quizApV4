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
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;



class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationLabel = 'Suallarım';

    protected static bool $isScopedToTenant = false;



    public static function getLabel(): string
    {
        return 'Sual';
    }

    public static function getPluralLabel(): string
    {
        return 'Suallar';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';


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
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Admin-ə bütün suallar lazım olacaq
        if (auth()->user()->is_admin ?? false) {
            return $query;
        }

        // Merchant user yalnız öz quizzinə aid sualları görə bilər
        $merchantId = auth()->user()->merchant_id;

        return $query->whereExists(function ($subQuery) use ($merchantId) {
            $subQuery->selectRaw(1)
                ->from('quiz_question_maps as qqm')
                ->join('merchant_quiz as mq', 'mq.quiz_id', '=', 'qqm.quiz_id')
                ->whereColumn('qqm.question_id', 'questions.id')
                ->where('mq.merchant_id', $merchantId);
        });
    }

    public static function afterCreate(Question $question)
    {
        // Burada `merchant_id`-ni istifadəçinin `merchant_id`-si ilə doldururuq
        $question->merchant_id = auth()->user()->merchant_id;
        $question->save();
    }
    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'view' => ViewQuestion::route('/{record}'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }
}
