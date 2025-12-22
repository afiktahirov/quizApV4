<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Merchant;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;

class RegisterMerchant extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Merchant';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name') // Merchant adı
                ->required(),
                TextInput::make('slug') // Slug
                ->required(),
            ]);
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    protected function handleRegistration(array $data): Merchant
    {
        // Yeni merchant yarat
        $merchant = Merchant::create($data);

        // Yeni yaradılan merchant-ı istifadəçiyə təyin et
        $merchant->users()->attach(auth()->user());

        return $merchant;
    }
}
