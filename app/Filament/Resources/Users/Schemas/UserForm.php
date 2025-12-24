<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),

                TextInput::make('name')
                    ->required(),

                Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('teams', 'name') // əgər həqiqətən belongsTo Merchant-dursa
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    // CREATE zamanı avtomatik random şifrə:
                    ->default(fn (string $context) => $context === 'create'
                        ? Str::password(12)  // və ya Str::random(12)
                        : null
                    )
                    ->minLength(8)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->copyable(copyMessage: 'Copied!', copyMessageDuration: 1500)
                    ->helperText('Şifrə avtomatik generasiya olunur, lazım olsa kopyala.'),

            ]);
    }
}
