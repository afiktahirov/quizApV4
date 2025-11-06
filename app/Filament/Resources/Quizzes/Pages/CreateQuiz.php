<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;


    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'super_admin'; // Yalnız admin yarada bilər
    }
}
