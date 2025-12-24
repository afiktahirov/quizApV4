<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;

class ListQuizzes extends ListRecords
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()?->role === 'super_admin'),

        ];
    }
}
