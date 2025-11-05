<?php

namespace App\Filament\Resources\QuizSessions\Pages;

use App\Filament\Resources\QuizSessions\QuizSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizSessions extends ListRecords
{
    protected static string $resource = QuizSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            CreateAction::make(),
        ];
    }
}
