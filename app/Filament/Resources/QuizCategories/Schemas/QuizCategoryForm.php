<?php

namespace App\Filament\Resources\QuizCategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

class QuizCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktiv', 'inactive' => 'Deaktiv'])
                    ->required()
                    ->default('active'),
            ]);
    }
}
