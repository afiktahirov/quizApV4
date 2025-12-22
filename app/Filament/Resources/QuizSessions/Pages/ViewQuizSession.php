<?php

namespace App\Filament\Resources\QuizSessions\Pages;

use App\Filament\Resources\QuizSessions\QuizSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewQuizSession extends ViewRecord
{
    protected static string $resource = QuizSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
