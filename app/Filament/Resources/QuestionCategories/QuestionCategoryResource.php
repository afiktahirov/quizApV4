<?php

namespace App\Filament\Resources\QuestionCategories;

use App\Filament\Resources\QuestionCategories\Pages\CreateQuestionCategory;
use App\Filament\Resources\QuestionCategories\Pages\EditQuestionCategory;
use App\Filament\Resources\QuestionCategories\Pages\ListQuestionCategories;
use App\Filament\Resources\QuestionCategories\Pages\ViewQuestionCategory;
use App\Filament\Resources\QuestionCategories\Schemas\QuestionCategoryForm;
use App\Filament\Resources\QuestionCategories\Schemas\QuestionCategoryInfolist;
use App\Filament\Resources\QuestionCategories\Tables\QuestionCategoriesTable;
use App\Models\QuestionCategory;
use Filament\Facades\Filament;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuestionCategoryResource extends Resource
{
    protected static ?string $model = QuestionCategory::class;

    protected static string|BackedEnum|null $navigationIcon = "heroicon-o-queue-list";

    protected static ?string $recordTitleAttribute = 'name'; // düzəliş burada



    protected static ?string $navigationLabel = 'Sual Kateqoriyaları';

    public static function getLabel(): string      { return 'Kateqoriya'; }
    public static function getPluralLabel(): string { return 'Kateqoriyalar'; }


    public static function form(Schema $schema): Schema
    {
        return QuestionCategoryForm::configure($schema);
    }

    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();
        return $u && $u->role === 'super_admin';
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuestionCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionCategories::route('/'),
            'create' => CreateQuestionCategory::route('/create'),
//            'view' => ViewQuestionCategory::route('/{record}'),
            'edit' => EditQuestionCategory::route('/{record}/edit'),
        ];
    }
}
