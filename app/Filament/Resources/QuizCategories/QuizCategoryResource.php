<?php

namespace App\Filament\Resources\QuizCategories;

use App\Filament\Resources\QuizCategories\Pages\CreateQuizCategory;
use App\Filament\Resources\QuizCategories\Pages\EditQuizCategory;
use App\Filament\Resources\QuizCategories\Pages\ListQuizCategories;
use App\Filament\Resources\QuizCategories\Schemas\QuizCategoryForm;
use App\Filament\Resources\QuizCategories\Tables\QuizCategoriesTable;
use App\Models\QuizCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuizCategoryResource extends Resource
{
    protected static ?string $model = QuizCategory::class;

    protected static ?string $navigationLabel = 'Kampanya kateqoriyaları';



    public static function getLabel(): string
    {
        return 'Kampanya kategoriyası';
    }

    public static function getPluralLabel(): string
    {
        return 'Kampanya kategoriyaları';
    }


    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-bar';




    protected static ?string $recordTitleAttribute = 'QuizCategory';

    public static function form(Schema $schema): Schema
    {
        return QuizCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizCategoriesTable::configure($table);
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
            'index' => ListQuizCategories::route('/'),
            'create' => CreateQuizCategory::route('/create'),
            'edit' => EditQuizCategory::route('/{record}/edit'),
        ];
    }
}
