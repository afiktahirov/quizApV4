<?php

namespace App\Filament\Resources\QuizSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuizSessionForm
{

    public static function canCreate(): bool
    {
        return false;
    }
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('merchant_id')
                    ->required()
                    ->numeric(),
                TextInput::make('store_id')
                    ->required()
                    ->numeric(),
                TextInput::make('quiz_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('started_at')
                    ->required(),
                DateTimePicker::make('finished_at'),
                TextInput::make('score_pct')
                    ->numeric()
                    ->default(null),
                Toggle::make('is_passed')
                    ->required(),
                TextInput::make('ip')
                    ->default(null),
                TextInput::make('device_fingerprint')
                    ->default(null),
                TextInput::make('channel')
                    ->required()
                    ->default('qr'),
            ]);
    }
}
