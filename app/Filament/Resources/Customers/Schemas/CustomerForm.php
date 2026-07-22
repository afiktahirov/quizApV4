<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required(),
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->default(null),
                TextInput::make('password')
                    ->label('Şifrə')
                    ->password()
                    ->revealable()
                    // Model-də 'hashed' cast var — burada yalnız dolu olduqda göndəririk
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create'),
            ]);
    }
}
