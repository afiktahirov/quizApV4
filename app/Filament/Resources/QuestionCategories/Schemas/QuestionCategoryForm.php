<?php

namespace App\Filament\Resources\QuestionCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;


class QuestionCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) =>
                    $set('slug', Str::slug($state))
                    ),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active'   => 'Aktiv',
                        'inactive' => 'Deaktiv',
                    ])->required()->default('active'),
            ]);
    }
}
