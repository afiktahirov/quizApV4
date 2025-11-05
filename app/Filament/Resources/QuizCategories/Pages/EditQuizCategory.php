<?php

namespace App\Filament\Resources\QuizCategories\Pages;

use App\Filament\Resources\QuizCategories\QuizCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuizCategory extends EditRecord
{
    protected static string $resource = QuizCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
