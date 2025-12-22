<?php

namespace App\Filament\Resources\QuizSessions\Pages;

use App\Filament\Resources\QuizSessions\QuizSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditQuizSession extends EditRecord
{
    protected static string $resource = QuizSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
