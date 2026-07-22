<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        // Merchant istifadəçisi yalnız öz adına kampaniya yarada bilər
        if (! $user->is_admin) {
            $data['merchant_id'] = $user->merchant_id;
        }

        return $data;
    }
}
