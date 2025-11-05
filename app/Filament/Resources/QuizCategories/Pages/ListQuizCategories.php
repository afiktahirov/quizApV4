<?php

namespace App\Filament\Resources\QuizCategories\Pages;

use App\Filament\Resources\QuizCategories\QuizCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizCategories extends ListRecords
{
    protected static string $resource = QuizCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
