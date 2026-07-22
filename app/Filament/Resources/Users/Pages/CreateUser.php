<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Filament::auth()->user();

        // merchant_admin yalnız öz müəssisəsinə kassir əlavə edə bilər
        if (! $user->is_admin) {
            $data['merchant_id'] = $user->merchant_id;
            $data['role']        = User::ROLE_CASHIER;
        }

        return $data;
    }
}
