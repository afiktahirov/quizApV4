<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        // Super adminin yaratdığı sual qlobal bazaya düşür (merchant_id = null),
        // merchant istifadəçisininki öz adına yazılır.
        $data['merchant_id'] = $user->is_admin ? null : $user->merchant_id;

        return $data;
    }
}
