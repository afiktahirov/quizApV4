<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $authUser = Filament::auth()->user();
        $isAdmin  = $authUser?->is_admin ?? false;

        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),

                TextInput::make('name')
                    ->required(),

                // Merchant seçimi yalnız super admin üçün;
                // merchant_admin-in yaratdığı istifadəçi avtomatik öz merchant-ına düşür (CreateUser).
                Select::make('merchant_id')
                    ->label('Müəssisə')
                    ->options(Merchant::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->visible($isAdmin)
                    ->helperText('super_admin üçün boş saxlayın'),

                Select::make('role')
                    ->label('Rol')
                    ->options($isAdmin
                        ? [
                            User::ROLE_SUPER_ADMIN    => 'Super Admin',
                            User::ROLE_MERCHANT_ADMIN => 'Müəssisə Admini',
                            User::ROLE_CASHIER        => 'Kassir',
                        ]
                        : [
                            User::ROLE_CASHIER => 'Kassir',
                        ])
                    ->default(User::ROLE_CASHIER)
                    ->required(),

                TextInput::make('password')
                    ->label('Şifrə')
                    ->password()
                    ->revealable()
                    ->default(fn (string $context) => $context === 'create' ? Str::password(12) : null)
                    ->minLength(8)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->helperText('Şifrə avtomatik generasiya olunur, lazım olsa kopyalayın.'),
            ]);
    }
}
